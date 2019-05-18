<?php

namespace Drupal\plotly_js\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\FieldFilteredMarkup;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Url;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManager;
use Drupal\Core\File\FileSystem;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Asset\LibraryDiscovery;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Utility\Token;

/**
 * Plugin implementation of the 'plotly_js_graph' widget.
 *
 * @FieldWidget(
 *   id = "plotly_js_graph_widget",
 *   module = "plotly_js",
 *   label = @Translation("Plotly.js Graph"),
 *   field_types = {
 *     "plotly_js_graph"
 *   }
 * )
 */
class PlotlyJsGraphWidget extends WidgetBase implements ContainerFactoryPluginInterface {
  /**
   * The name of the field for this graph.
   *
   * @var string
   */
  protected $fieldName;
  /**
   * The index of the graph we want to build.
   *
   * @var int
   */
  protected $graphIndex;
  /**
   * The index of the series we want to build.
   *
   * @var int
   */
  protected $seriesIndex;
  /**
   * Existing defaults for trace data.
   *
   * @var array
   */
  protected $seriesSettings;
  /**
   * Existing defaults for layout data.
   *
   * @var array
   */
  protected $layoutSettings;
  /**
   * Reference for #states keys in form builder.
   *
   * @var string
   */
  protected $stateReference;

  /**
   * Drupal StreamWrapperManager service container.
   *
   * @var Drupal\Core\StreamWrapper\StreamWrapperManager
   */
  protected $streamWrapper;
  /**
   * Drupal FileSystem service container.
   *
   * @var Drupal\Core\File\FileSystem
   */
  protected $fileSystemService;
  /**
   * Drupal LoggerFactory service container.
   *
   * @var Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;
  /**
   * Drupal LibraryDiscovery service container.
   *
   * @var Drupal\Core\Asset\LibraryDiscovery
   */
  protected $libraryDiscovery;
  /**
   * Drupal LoggerFactory service container.
   *
   * @var Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;
  /**
   * Drupal RouteMatchInterface service container.
   *
   * @var Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;
  /**
   * Drupal token service container.
   *
   * @var Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * {@inheritdoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, StreamWrapperManager $stream_wrapper_manager, FileSystem $file_system, LoggerChannelFactoryInterface $logger_factory, LibraryDiscovery $library_discovery, ConfigFactory $config_factory, RouteMatchInterface $route_match, Token $token) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);

    $this->streamWrapper = $stream_wrapper_manager;
    $this->fileSystemService = $file_system;
    $this->loggerFactory = $logger_factory;
    $this->libraryDiscovery = $library_discovery;
    $this->configFactory = $config_factory;
    $this->routeMatch = $route_match;
    $this->token = $token;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('stream_wrapper_manager'),
      $container->get('file_system'),
      $container->get('logger.factory'),
      $container->get('library.discovery'),
      $container->get('config.factory'),
      $container->get('current_route_match'),
      $container->get('token')
    );
  }

  /**
   * Loads an external Plotly definition file (either user-defined or local).
   *
   * @param string $plotname
   *   The name of the plot we are loading.
   *
   * @return array
   *   An array of data loaded from the file.
   */
  private function loadExternalPlotlyTemplateFile($plotname) {
    // Create a list of possible template names.
    $suggested_template_names = [
      $plotname . '--' . $this->fieldDefinition->getTargetEntityTypeId() . '--' . $this->fieldDefinition->getTargetBundle(),
      $plotname . '--' . $this->fieldDefinition->getTargetEntityTypeId(),
      $plotname,
    ];
    // Get the node ID if it exists.
    $node = $this->routeMatch->getParameter('node');
    if ($node) {
      // Create additional template suggestions for this node.
      array_unshift($suggested_template_names, $plotname . '--' . $this->fieldDefinition->getTargetEntityTypeId() . '--' . $node->id());
      array_unshift($suggested_template_names, $plotname . '--' . $this->fieldDefinition->getTargetEntityTypeId() . '--' . $node->id() . '--' . $this->fieldDefinition->getTargetBundle());
    }

    // Get the path to custom graph templates.
    $graph_template_path = $this->configFactory->get('plotly_js.settings')->get('graph_template_path');
    // Loop over each possible template name and see if it exists.
    foreach ($suggested_template_names as $possible_template_name) {
      // Generate stream wrapper for the custom graph template.
      $stream_wrapper = $this->streamWrapper->getViaUri($graph_template_path . '/' . $possible_template_name . '.yml');
      // If the graph template exists in the user space, use that.
      if (file_exists($stream_wrapper->realpath())) {
        $actual_template_path = $stream_wrapper->realpath();
        break;
      }
    }
    // If we didn't find a user template, use the default.
    if (!isset($actual_template_path)) {
      $actual_template_path = $this->fileSystemService->realpath(DRUPAL_ROOT . '/' . drupal_get_path('module', 'plotly_js') . '/graph_templates/' . $plotname . '.yml');
    }

    // Parse the YAML data.
    try {
      return Yaml::parse(file_get_contents($actual_template_path));
    }
    catch (ParseException $e) {
      $message_data = [
        '%template' => $plotname,
        '%exception' => $e->getMessage(),
      ];
      $this->loggerFactory->get('plotly_js')->error('Error: Unable to load graph template for %template: invalid Yaml. %exception', $message_data);
      $this->messanger()->addError($this->t('Error: Unable to load graph template for %template: invalid Yaml. %exception', $message_data), 'error');
      return [];
    }
  }

  /**
   * Converts template data file to a Drupal settings form.
   *
   * @param string $formname
   *   The name of the form we are building.
   * @param array $element_parents
   *   The parent keys for the form being built.
   * @param int $delta
   *   The delta for the graph being rendered.
   * @param Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   *
   * @return array
   *   Drupal form elements reflecting settings for the data fields.
   */
  private function buildFormFromTemplate($formname, array $element_parents, $delta, FormStateInterface $form_state) {
    $form_fields = [];

    $visibility_references = [];
    // For series data, we add a select field for the type.
    if ($formname != 'layout') {
      $form_fields['type'] = [
        '#type' => 'select',
        '#title' => $this->t('Series Type'),
        '#options' => [
          'none' => 'None',
          'area' => 'Area Chart',
          'bar' => 'Bar Chart',
          'box' => 'Box Plot',
          'candlestick' => 'Candlestick Plot',
          'carpet' => 'Carpet Plot',
          'choropleth' => 'Choropleth Maps',
          'contour' => 'Contour Plot',
          'contourcarpet' => 'Contour Carpet Plot',
          'heatmap' => 'Heatmap',
          'heatmapgl' => 'Heatmap (WebGL version)',
          'histogram' => 'Histogram',
          'histogram2d' => 'Histogram (2D)',
          'histogram2dcontour' => 'Histogram (2D Contour)',
          'mesh3d' => 'Mesh (3D)',
          'ohlc' => 'Open-High-Low-Close (OHLC) Charts',
          'parcoords' => 'Parallel Coordinates',
          'pie' => 'Pie Chart',
          'pointcloud' => 'Point Cloud',
          'sankey' => 'Sankey Diagram',
          'scatter' => 'Scatter Plot',
          'scatter3d' => 'Scatter Plot (3D)',
          'scattercarpet' => 'Scatter Plot (Carpet)',
          'scattergeo' => 'Scatter Geo Plot',
          'scattergl' => 'Scatter Plot (WebGL version)',
          'scattermapbox' => 'Scatter Map Plot (Mapbox integration)',
          'surface' => 'Surface Plot (3D)',
          'scatterternary' => 'Ternary Scatter Plot',
        ],
        '#default_value' => $formname,
        '#description' => $this->t('The type of series which this graph entry represents. Configuration options for this series type will be available after you select this option and save. Choosing `None` will have the effect of deleting this series. For more information on series types, see <a href=":link">:link</a>', [
          ':link' => 'https://plot.ly/javascript/#basic-charts',
        ]),
      ];
      // Add a dependence so all fields are dependent on this selection.
      $visibility_references[':input[name="' . $this->stateReference . '[' . implode('][', $element_parents) . '][0][type]"]'] = ['value' => $formname];
    }

    // If this is a mapbox type, check if we have a token.
    if ($formname == 'scattermapbox' && $this->configFactory->get('plotly_js.settings')->get('mapbox_access_token') == '') {
      $form_fields['error'] = [
        '#type' => 'details',
        '#open' => TRUE,
        '#title' => $this->t('Error'),
        [
          '#type' => 'container',
          '#attributes' => [],
          '#title' => $this->t('Error'),
          '#children' => $this->t('Missing mapbox access token for series type %type. Please enter your mapbox access token at <a href=":url">Plotly.Js module settings page</a>', [
            '%type' => $formname,
            ':url' => Url::fromRoute('plotly_js.admin_settings')->toString(),
          ]),
        ],
      ];
    }
    // If a formname has been selected, load it.
    elseif (!empty($formname)) {
      // Load the template data.
      $form_data = $this->loadExternalPlotlyTemplateFile($formname);
      if (isset($form_data['fields'])) {
        $form_fields += $this->processFormTemplateFields($form_data['fields'], $element_parents, $visibility_references, $delta, $form_state);
      }
    }
    return $form_fields;
  }

  /**
   * Converts template field definitions to Drupal settings form elements.
   *
   * @param array $data
   *   The array of template data about these fields.
   * @param array $element_parents
   *   The parent keys for the form being built.
   * @param array $visibility_references
   *   An array of '#states' visibility references for these fields..
   * @param int $delta
   *   The delta for which graph is being rendered.
   * @param Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   *
   * @return array
   *   Drupal form elements reflecting settings for the data fields.
   */
  private function processFormTemplateFields(array $data, array $element_parents, array $visibility_references, $delta, FormStateInterface $form_state) {
    $elements = [];
    foreach ($data as $field_data) {
      // Check for completely broken field definitions.
      if (!isset($field_data['fieldname'])) {
        $message_data = [
          '@fieldname' => $field_data,
        ];
        $this->loggerFactory->get('plotly_js')->error('Error: Plotly found a broken field definition: @fieldname.', $message_data);
        $this->messanger()->addError($this->t('Error: Plotly found a broken field definition: @fieldname.', $message_data), 'error');
        return [];
      }

      // If this has subfields, its a parent container.
      if (isset($field_data['subfields'])) {
        // Build the parent container.
        $elements[$field_data['fieldname']] = [
          '#type' => 'details',
          '#title' => isset($field_data['title']) ? $field_data['title'] : $field_data['fieldname'],
          '#open' => FALSE,
          '#description' => $this->t('Documentation name `%data`.', [
            '%data' => $field_data['fieldname'],
          ]) . (isset($field_data['description']) ? ' ' . $field_data['description'] : ''),
          '#states' => [
            'visible' => $visibility_references,
          ],
        ];
        // Check to see if this is a multifield or not.
        if (isset($field_data['multifield']) && $field_data['multifield']) {
          // Get a valid field identifier.
          $field_identifier = Html::cleanCssIdentifier(implode('-', $element_parents) . '-' . $field_data['fieldname'] . '-' . $delta);

          // Gather the number of entries in the form already.
          $number_of_values = $form_state->get($field_identifier . '-number-of-values');
          // If the number of series hasn't been set by the form, get it.
          if ($number_of_values === NULL) {
            // Number of series comes from count of series settings.
            $count_values = $this->getDefaultValue($element_parents, $field_data['fieldname'], []);
            if (is_array($count_values)) {
              $count_values = count($count_values);
            }
            $form_state->set(($field_identifier . '-number-of-values'), $count_values);
            $number_of_values = $form_state->get($field_identifier . '-number-of-values');
          }
          // Container for series data.
          $elements[$field_data['fieldname']]['#tree'] = TRUE;
          $elements[$field_data['fieldname']] = [
            '#type' => 'details',
            '#open' => FALSE,
            '#prefix' => '<div id="' . $field_identifier . '-fieldset-wrapper">',
            '#suffix' => '</div>',
            '#tree' => TRUE,
          ];
          // Set the title.
          if (isset($field_data['title'])) {
            // Use the expressly defined title element if there is one.
            $elements[$field_data['fieldname']]['#title'] = $field_data['title'];
          }
          else {
            // Use the field name.
            $elements[$field_data['fieldname']]['#title'] = Unicode::ucwords($field_data['fieldname']);
          }

          // Loop over the current number of entries and print a form for each.
          for ($i = 0; $i < $number_of_values; $i++) {
            // Build the children of this container.
            $elements[$field_data['fieldname']][$i] = [
              '#type' => 'details',
              '#title' => $this->t('@field Value @valuenum', [
                '@field' => $field_data['fieldname'],
                '@valuenum' => ($i + 1),
              ]),
              '#open' => FALSE,
              '#states' => [
                'visible' => $visibility_references,
              ],
              'delete' => [
                '#type' => 'select',
                '#title' => $this->t('Delete?'),
                '#options' => [
                  'false' => $this->t('No'),
                  'true' => $this->t('Yes'),
                ],
                '#default_value' => 'false',
                '#description' => $this->t('Setting this flag will delete this entry'),
              ],
            ];
            $elements[$field_data['fieldname']][$i] += $this->processFormTemplateFields($field_data['subfields'], array_merge(array_merge($element_parents, [$field_data['fieldname']]), [$i]), $visibility_references, $delta, $form_state);
          }

          // Button to add additional entries to the graph.
          $elements[$field_data['fieldname']]['ajax_actions'] = [
            '#type' => 'actions',
            'add_entry' => [
              '#type' => 'submit',
              '#name' => 'add_' . $field_identifier,
              '#value' => $this->t('Add Additional @fieldname', [
                '@fieldname' => $elements[$field_data['fieldname']]['#title'],
              ]),
              '#submit' => [
                [$this, 'addEntriesGenericOne'],
              ],
              '#ajax' => [
                'callback' => [$this, 'addEntriesGenericCallback'],
                'wrapper' => $field_identifier . '-fieldset-wrapper',
                'method' => 'replaceWith',
              ],
            ],
          ];
        }
        else {
          // Build the children of this container.
          $elements[$field_data['fieldname']] += $this->processFormTemplateFields($field_data['subfields'], array_merge($element_parents, [$field_data['fieldname']]), $visibility_references, $delta, $form_state);
        }
      }
      // This is an actual defined field entity (ignores src attributes).
      elseif (isset($field_data['values'])) {
        // Handle enumerated values.
        if (is_array($field_data['values'])) {
          // If there's not a default, allow blank.
          if (!isset($field_data['default'])) {
            array_unshift($field_data['values'], '');
          }
          // Trim the values.
          foreach ($field_data['values'] as &$enumval) {
            $enumval = trim($enumval);
          }

          $field_options = $field_data['values'];
          // If this is not associative, we use the values as the keys.
          if (!$this->isArrayAssociative($field_data['values'])) {
            $field_options = array_combine($field_data['values'], $field_data['values']);
          }

          $field_definition = [
            '#type' => 'select',
            '#options' => $field_options,
          ];
        }
        else {

          // This is a form element - need to build its definition.
          switch ($field_data['values']) {

            case 'subplotid':
              $field_definition = [
                '#type' => 'textfield',
                '#size' => 50,
                '#description' => $this->t('Please note that anything other than the default value in this field requires a custom axis implentation in the graph template files.') . ' ',
              ];
              break;

            case 'string':
            case 'string or array of strings':
            case 'number or categorical coordinate string':
              $field_definition = [
                '#type' => 'textfield',
                '#size' => 50,
              ];
              break;

            case 'color':
              // Determine the state refence for visibility of the color.
              $color_visibility = $element_parents;
              if ($color_visibility[0] == 'layout') {
                array_splice($color_visibility, 1, 0, [0]);
              }
              else {
                array_splice($color_visibility, 2, 0, [0]);
              }
              $color_visibility = $this->stateReference . '[' . implode('][', $color_visibility) . '][' . $field_data['fieldname'] . '][use_' . $field_data['fieldname'] . ']';
              // Color doesn't allow blank, so include a select option.
              $field_definition = [
                '#type' => 'details',
                '#open' => TRUE,
                'use_' . $field_data['fieldname'] => [
                  '#type' => 'select',
                  '#title' => $this->t('Use Custom Color?'),
                  '#description' => $this->t('Selecting `True` will allow you to select a custom color.'),
                  '#options' => [
                    'true' => $this->t('True'),
                    'false' => $this->t('False'),
                  ],
                  '#default_value' => $this->getDefaultValue($element_parents, 'use_' . $field_data['fieldname'], 'false'),
                ],
                1 => [
                  '#type' => 'color',
                  '#states' => [
                    'visible' => [
                      ':input[name="' . $color_visibility . '"]' => ['value' => 'true'],
                    ],
                  ],
                ],
              ];
              break;

            case 'boolean':
              $field_definition = [
                '#type' => 'select',
                '#options' => [
                  '' => '',
                  'true' => $this->t('True'),
                  'false' => $this->t('False'),
                ],
              ];
              break;

            case 'data array':
              $field_definition = [
                '#type' => 'textarea',
                '#rows' => 2,
                '#description' => $this->t('Data should be input in the format VALUE1,VALUE2,VALUE3,VALUE4,etc... Multiple arrays should be entered one on each line.') . ' ',
              ];
              break;

            case 'array':
              $field_definition = [
                '#type' => 'textfield',
                '#size' => 50,
                '#description' => $this->t('Data should be input in the format VALUE1,VALUE2.') . ' ',
                '#field_prefix' => '[',
                '#field_suffix' => ']',
                '#element_validate' => [
                  [static::class, 'validateFieldArray'],
                ],
              ];
              break;

            case 'angle':
              $field_definition = [
                '#type' => 'textfield',
                '#size' => 50,
                '#description' => $this->t('Value entered should be a numeric angle or `auto`.') . ' ',
                '#element_validate' => [
                  [static::class, 'validateFieldAngle'],
                ],
              ];
              break;

            case 'number':
              $field_definition = [
                '#type' => 'number',
                '#step' => 0.01,
              ];
              break;

            case 'integer':
              $field_definition = [
                '#type' => 'number',
                '#step' => 1,
              ];
              break;

            case 'number or array of numbers':
              $field_definition = [
                '#type' => 'textfield',
                '#size' => 50,
                '#description' => $this->t('Data should be numeric values input in the format VALUE1,VALUE2,VALUE3,VALUE4,etc...') . ' ',
                '#element_validate' => [
                  [static::class, 'validateFieldNumberArray'],
                ],
              ];
              break;

            case 'integer or array of integers':
              $field_definition = [
                '#type' => 'textfield',
                '#size' => 50,
                '#description' => $this->t('Data should be numeric values input in the format VALUE1,VALUE2,VALUE3,VALUE4,etc...') . ' ',
                '#element_validate' => [
                  [static::class, 'validateFieldIntegerArray'],
                ],
              ];
              break;

            default:
              // Ignore graph type (built in the parent function).
              if ($field_data['fieldname'] != 'type') {
                // We haven't built the field definition yet.
                $message_data = [
                  '@fieldname' => $field_data['fieldname'],
                  '@values' => $field_data['values'],
                ];
                $this->loggerFactory->get('plotly_js')->error('Error: Plotly is missing a definition for @fieldname: @values.', $message_data);
                $this->messanger()->addError($this->t('Error: Plotly is missing a definition for @fieldname: @values.', $message_data), 'error');
              }
              $field_definition = [];
              break;
          }
        }

        // Postprocess field definitions.
        if (isset($field_definition['#type'])) {
          // Set visibility #states references.
          if (count($visibility_references) > 0) {
            $field_definition['#states'] = [
              'visible' => $visibility_references,
            ];
          }
          // Set the default values for normal form elements.
          if ($field_definition['#type'] != 'details') {
            $this->setGraphFieldFormSettings($field_definition, $field_data, $element_parents);
          }
          // Handle if its a multi-value field (colors, for example).
          elseif ($field_definition[1]['#type'] == 'color') {
            // Set the title.
            if (isset($field_data['title'])) {
              // Use the expressly defined title element if there is one.
              $field_definition['#title'] = $field_data['title'];
            }
            else {
              // Use the field name.
              $field_definition['#title'] = Unicode::ucwords($field_data['fieldname']);
            }
            // Add the documentation name to the field.
            $field_definition['#description'] = $this->t('Documentation name `%data`.', [
              '%data' => $field_data['fieldname'],
            ]) . (isset($field_definition['#description']) ? ' ' . $field_definition['#description'] : ' ');

            // Set defaults for the color portion.
            $this->setGraphFieldFormSettings($field_definition[1], $field_data, $element_parents);
            // Put the color description into the details if we can.
            if (isset($field_definition[1]['#description'])) {
              $field_definition['#description'] = $field_definition[1]['#description'];
            }
          }
        }

        // Add the field to the form.
        $elements[$field_data['fieldname']] = $field_definition;
      }
    }
    return $elements;
  }

  /**
   * Determines if an array is associative.
   *
   * @param array $array
   *   The array being checked.
   */
  private function isArrayAssociative(array $array) {
    if ([] === $array) {
      return FALSE;
    }
    return array_keys($array) !== range(0, count($array) - 1);
  }

  /**
   * Set form element default values for graph fields.
   *
   * @param array &$field_definition
   *   The form array definition for the field.
   * @param array $field_data
   *   The template data about the field.
   * @param array $element_parents
   *   The parents of this field..
   */
  private function setGraphFieldFormSettings(array &$field_definition, array $field_data, array $element_parents) {
    if ($field_data['values'] == 'color') {
      // Convert RGB if we need to.
      if (isset($field_data['default']) && substr($field_data['default'], 0, 3) == 'rgb') {
        $field_data['default'] = $this->rgbConvertToHex($field_data['default']);
      }
      // Convert 3 digit hex if we need to.
      elseif (isset($field_data['default']) && strlen($field_data['default']) == 4) {
        $field_data['default'] = $this->threeHexConvertToHex($field_data['default']);
      }
      // Convert 3 digit hex if we need to.
      elseif (!isset($field_data['default'])) {
        $field_data['default'] = '#000000';
      }
    }
    // Set the description.
    if (isset($field_data['description'])) {
      // Set a blank description for empty, allows append descriptions.
      if (!isset($field_definition['#description'])) {
        $field_definition['#description'] = '';
      }
      $field_definition['#description'] .= $field_data['description'];
      // Set the field suffix.
      if (preg_match('%(.*)\(in px\)(.*)%', $field_data['description'])) {
        $field_definition['#field_suffix'] = 'px';
      }
    }
    // Add the documentation name to the field.
    $field_definition['#description'] = $this->t('Documentation name `%data`.', [
      '%data' => $field_data['fieldname'],
    ]) . (isset($field_definition['#description']) ? ' ' . $field_definition['#description'] : ' ');
    // Set the title.
    if (isset($field_data['title'])) {
      // Use the expressly defined title element if there is one.
      $field_definition['#title'] = $field_data['title'];
    }
    else {
      // Use the field name.
      $field_definition['#title'] = Unicode::ucwords($field_data['fieldname']);
    }
    // Set the default value.
    $field_definition['#default_value'] = $this->getDefaultValue($element_parents, $field_data['fieldname'], (isset($field_data['default']) ? $field_data['default'] : ''));
    // Set the min/max values.
    if (isset($field_data['min'])) {
      $field_definition['#min'] = $field_data['min'];
    }
    if (isset($field_data['max'])) {
      $field_definition['#max'] = $field_data['max'];
    }
    // Convert to range if value is between 0 and 1.
    if (($field_data['values'] == 'number' || $field_data['values'] == 'integer') && isset($field_definition['#min']) && $field_definition['#min'] == 0 && isset($field_definition['#max']) && $field_definition['#max'] == 1) {
      $field_definition['#type'] = 'range';
    }
  }

  /**
   * Validate the array text field.
   */
  public static function validateFieldArray($element, FormStateInterface $form_state) {
    $value = $element['#value'];
    if (strlen($value) == 0) {
      $form_state->setValueForElement($element, '');
      return;
    }
    if (!preg_match('%[\-\.0-9]+,[\s]?[\-\.0-9]+%', strtolower($value))) {
      $form_state->setError($element, t("Array values must be in the format `VALUE1, VALUE2`. %value found.", [
        '%value' => $value,
      ]));
    }
  }

  /**
   * Validate the angle text field.
   */
  public static function validateFieldAngle($element, FormStateInterface $form_state) {
    $value = $element['#value'];
    if (strlen($value) == 0) {
      $form_state->setValueForElement($element, '');
      return;
    }
    if (!is_numeric($value) && $value != 'auto') {
      $form_state->setError($element, t("Angle values must be a numeric value or set to `auto`. %value found.", [
        '%value' => $value,
      ]));
    }
  }

  /**
   * Validate the numeric array text field.
   */
  public static function validateFieldNumberArray($element, FormStateInterface $form_state) {
    $value = $element['#value'];
    if (strlen($value) == 0) {
      $form_state->setValueForElement($element, '');
      return;
    }
    $components = explode(',', $value);
    foreach ($components as $component) {
      if (!is_numeric($component)) {
        $form_state->setError($element, t("Numeric array values must be numeric values entered in the format `VALUE1,VALUE2,VALUE3,etc...`. %value found.", [
          '%value' => $value,
        ]));
      }
    }
  }

  /**
   * Validate the integer array text field.
   */
  public static function validateFieldIntegerArray($element, FormStateInterface $form_state) {
    $value = $element['#value'];
    if (strlen($value) == 0) {
      $form_state->setValueForElement($element, '');
      return;
    }
    $components = explode(',', $value);
    foreach ($components as $component) {
      if (!filter_var($component, FILTER_VALIDATE_INT)) {
        $form_state->setError($element, t("Integer array values must be integer values entered in the format `VALUE1,VALUE2,VALUE3,etc...`. %value found.", [
          '%value' => $value,
        ]));
      }
    }
  }

  /**
   * Helper function to convert RGBA to Hex.
   *
   * @param string $input
   *   The rgba value for converting.
   *
   * @return string
   *   A properly formatted hex value.
   */
  private function rgbConvertToHex($input) {
    $rgb = [];
    preg_match('%rgb(a)?\(([0-9]+),[\s]?([0-9]+),[\s]?([0-9]+),?[\s]?([0-9\.]+)?\)%', $input, $rgb);
    return '#' . sprintf('%02x', $rgb[2]) . sprintf('%02x', $rgb[3]) . sprintf('%02x', $rgb[4]);
  }

  /**
   * Helper function to convert 3 digit hex to 6 digit.
   *
   * @param string $input
   *   The 3 digit hex value for converting.
   *
   * @return string
   *   A properly formatted hex value.
   */
  private function threeHexConvertToHex($input) {
    return '#' . $input[1] . $input[1] . $input[2] . $input[2] . $input[3] . $input[3];
  }

  /**
   * Get the default value for a series form element.
   *
   * @param array $keys
   *   The keys for the value we're retrieving.
   * @param string $fieldname
   *   The name of the field we're retrieving.
   * @param mixed $default
   *   The default value if the key is not found.
   *
   * @return mixed
   *   The default value of this field.
   */
  protected function getDefaultValue(array $keys, $fieldname, $default = '') {
    if ($keys[0] == 'layout') {
      $lookup_setting = $this->layoutSettings;
    }
    elseif ($keys[0] == 'series_data') {
      $lookup_setting = $this->seriesSettings;
    }
    // Remove the first key as its just the name of the setting array.
    array_shift($keys);
    // Loop through the trace data and find the value we need.
    foreach ($keys as $lookup_key) {
      // If any part of the lookup key is not found, return the default.
      if (!isset($lookup_setting[$lookup_key])) {
        return $default;
      }
      $lookup_setting = $lookup_setting[$lookup_key];
    }
    // At this point we should have the parent array so we check for child.
    if (isset($lookup_setting[$fieldname])) {
      // Handle situations where value is an array - have to convert back.
      if (is_array($lookup_setting[$fieldname])) {
        // If this is an ajax list, we just return it.
        if (isset($lookup_setting[$fieldname]['ajax_actions'])) {
          unset($lookup_setting[$fieldname]['ajax_actions']);
          return $lookup_setting[$fieldname];
        }
        // Check if its a subarray.
        $subarray = FALSE;
        foreach ($lookup_setting[$fieldname] as &$subfield) {
          if ($subarray = is_array($subfield)) {
            $subfield = implode(',', $subfield);
          }
        }
        $lookup_setting[$fieldname] = implode($subarray ? PHP_EOL : ',', $lookup_setting[$fieldname]);
      }
      return $lookup_setting[$fieldname];
    }
    return $default;
  }

  /**
   * Get the title of this series.
   *
   * @return string
   *   The name of this series.
   */
  protected function getSeriesTitle() {
    return ((isset($this->seriesSettings[$this->seriesIndex]['name']) && !empty($this->seriesSettings[$this->seriesIndex]['name'])) ? ('`' . $this->seriesSettings[$this->seriesIndex]['name'] . '`') : $this->t('Series @number', [
      '@number' => ($this->seriesIndex + 1),
    ]));
  }

  /**
   * Set the series index for this series.
   *
   * @param int $series_index
   *   The index of the series we want to build.
   */
  protected function setSeriesIndex($series_index) {
    // Set the series index.
    $this->seriesIndex = $series_index;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    // Verify the library is installed.
    $plotly_library = $this->libraryDiscovery->getLibraryByName('plotly_js', 'plotly_js.plotly');
    $install_path = $plotly_library['js'][0]['data'];

    // Check if we are using external file.
    $configuration_settings = $this->configFactory->get('plotly_js.settings')->get();

    if (!$configuration_settings['use_external'] && !file_exists(DRUPAL_ROOT . '/' . $install_path)) {
      $message_data = [
        ':remotepath' => $plotly_library['remote'],
        '%installpath' => $install_path,
        ':config' => Url::fromRoute('plotly_js.admin_settings')->toString(),
      ];
      $this->loggerFactory->get('plotly_js')->error('Error: Plotly.Js has not been installed. Please download from <a href=":remotepath">:remotepath</a> and install at %installpath, or visit the <a href=":config">Plotly.Js settings page</a> and choose an external file.', $message_data);
      $this->messanger()->addError($this->t('Error: Plotly.Js has not been installed. Please download from <a href=":remotepath">:remotepath</a> and install at %installpath, or visit the <a href=":config">Plotly.Js settings page</a> and choose an external file.', $message_data), 'error');
    }

    // Set the data for this graph.
    $this->fieldName = $this->fieldDefinition->getName();
    $this->graphIndex = $delta;
    $this->seriesSettings = unserialize($items[$delta]->series_data);
    $this->layoutSettings = unserialize($items[$delta]->layout);
    $this->stateReference = $this->fieldName . '[' . $this->graphIndex . ']';

    $element['graph_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Graph Name'),
      '#size' => 50,
      '#default_value' => isset($items[$delta]->graph_name) ? $items[$delta]->graph_name : '',
      '#description' => $this->t('Leaving this field empty will delete this graph.'),
    ];

    // Gather the number of series in the form already.
    $number_of_series = $form_state->get('number_of_series-' . $delta);
    // If the number of series hasn't been set by the form, get it.
    if ($number_of_series === NULL) {
      // Number of series comes from count of series settings.
      $number_of_series = ($this->seriesSettings instanceof Countable) ? max(count($this->seriesSettings), 1) : 1;
      $form_state->set(('number_of_series-' . $delta), $number_of_series);
    }
    // Container for series data.
    $element['#tree'] = TRUE;
    $element['series_data'] = [
      '#type' => 'details',
      '#title' => $this->t('Series Data'),
      '#open' => TRUE,
      '#prefix' => '<div id="series-fieldset-wrapper-' . $delta . '">',
      '#suffix' => '</div>',
      // Disable if the graph name is not set.
      '#states' => [
        'disabled' => [
          ':input[name="' . $this->fieldName . '[' . $delta . '][graph_name]"]' => ['value' => ''],
        ],
      ],
    ];

    // Loop over the current number of series and print a form for each.
    for ($series_index = 0; $series_index < $number_of_series; $series_index++) {
      // Set the series index value.
      $this->setSeriesIndex($series_index);

      // Determine the selected series type.
      $series_type = '';
      if (isset($this->seriesSettings[$this->seriesIndex]['type'])) {
        $series_type = $this->seriesSettings[$this->seriesIndex]['type'];
      }

      // Build out the trace settings for this particular graph type.
      $element['series_data'][$series_index] = [
        '#type' => 'details',
        '#title' => $this->getSeriesTitle(),
        '#open' => FALSE,
        // Load the form for the widget associated with the selected graph.
        0 => $this->buildFormFromTemplate($series_type, ['series_data', $series_index], $delta, $form_state),
      ];
    }

    // Button to add additional series to the graph.
    $element['series_data']['ajax_actions'] = [
      '#type' => 'actions',
      'add_entry' => [
        '#type' => 'submit',
        '#name' => 'add_entry-' . $delta,
        '#value' => $this->t('Add Additional Series'),
        '#submit' => [
          [$this, 'addSeriesOne'],
        ],
        '#ajax' => [
          'callback' => [$this, 'addEntriesGenericCallback'],
          'wrapper' => 'series-fieldset-wrapper-' . $delta,
          'method' => 'replaceWith',
        ],
      ],
    ];
    // Layout data for the graph.
    $element['layout'] = [
      '#type' => 'details',
      '#title' => $this->t('Layout'),
      '#open' => FALSE,
      // Disable if the graph name is not set.
      '#states' => [
        'disabled' => [
          ':input[name="' . $this->fieldName . '[' . $delta . '][graph_name]"]' => ['value' => ''],
        ],
      ],
      0 => $this->buildFormFromTemplate('layout', ['layout'], $delta, $form_state),
    ];

    // Responsive setting for the graph.
    $element['responsive'] = [
      '#type' => 'select',
      '#title' => $this->t('Responsive'),
      '#options' => [
        'false' => $this->t('False'),
        'true' => $this->t('True'),
      ],
      '#default_value' => isset($this->layoutSettings['responsive']) ? $this->layoutSettings['responsive'] : 'false',
    ];

    $form_state->setCached(FALSE);

    return $element;
  }

  /**
   * Submit handler for the "add-additional-series" button.
   *
   * Increments the number of series and causes a rebuild.
   */
  public function addSeriesOne(array &$form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    // Get the delta of the calling element.
    $delta = $triggering_element['#parents'][1];
    // Increment the number of series.
    $form_state->set('number_of_series-' . $delta, $form_state->get('number_of_series-' . $delta) + 1);
    // Rebuild the form.
    $form_state->setRebuild();
  }

  /**
   * Callback for add additional generic entries ajax.
   *
   * Selects and returns the fieldset with the entry in it.
   */
  public function addEntriesGenericCallback(array &$form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    // Get the calling element.
    $calling_element = $form;
    foreach (array_slice($triggering_element['#array_parents'], 0, array_search('ajax_actions', $triggering_element['#array_parents'], TRUE)) as $parent) {
      $calling_element = $calling_element[$parent];
    }
    return $calling_element;
  }

  /**
   * Submit handler for the "add-additional-entries" button.
   *
   * Increments the number of entries (generic) and causes a rebuild.
   */
  public function addEntriesGenericOne(array &$form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    // Get the identifier of the calling element.
    $delta = $triggering_element['#parents'][1];
    $subparents = array_slice($triggering_element['#parents'], 2, (count($triggering_element['#parents']) - 4));
    unset($subparents[1]);
    $field_identifier = Html::cleanCssIdentifier(implode('-', $subparents) . '-' . $delta);
    // Increment the number of entries.
    $form_state->set($field_identifier . '-number-of-values', $form_state->get($field_identifier . '-number-of-values') + 1);
    // Rebuild the form.
    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    // Throw error if there was an issue with number of variables.
    $number_form_elements = count($values, COUNT_RECURSIVE);
    if ($number_form_elements >= ini_get('max_input_vars')) {
      $message_data = [
        '@max' => ini_get('max_input_vars'),
        '@number' => $number_form_elements,
        '%variablename' => 'max_input_vars',
        ':link' => 'http://php.net/manual/en/info.configuration.php#ini.max-input-vars',
      ];
      $this->loggerFactory->get('plotly_js')->error('Error: Unable to save form; the number of Plotly.js fields exceeds the limit set on your server. The current limit is @max, and this form has @number fields. Please increase your allowed number of input variables by adjusting %variablename, or reduce the number of form fields by removing unnecessary fields from the plot template definition. See :link for more information', $message_data);
      $this->messanger()->addError($this->t('Error: Unable to save form; the number of fields exceeds the limit set on your server. The current limit is @max, and this form has @number fields. Please increase your allowed number of input variables by adjusting %variablename, or reduce the number of form fields by removing unnecessary fields from the plot templateemplate definition. See <a href=":link">:link</a> for more information.', $message_data), 'error');
      return;
    }

    // Loop over each data set and massage the data.
    foreach ($values as $graph_index => &$item) {
      // Remove the 'actions' values.
      unset($item['series_data']['ajax_actions']);

      // Get the number of current series from the count of series data.
      $item['number_of_series'] = count($item['series_data']);

      if (!empty($item['graph_name'])) {
        // Set number of series to 1 if it was blank.
        if ($item['number_of_series'] == '') {
          $item['number_of_series'] = 1;
        }

        // Create an empty array for the series data if it isn't set.
        if (!isset($item['series_data'])) {
          $item['series_data'] = [];
        }

        // Go through the series and delete if need be.
        foreach ($item['series_data'] as $key => $seriesData) {
          $seriesData = $seriesData[0];
          // If `none` selected as series type, or we removed it, wipe the data.
          if ($item['number_of_series'] <= $key || $seriesData['type'] == 'none') {
            unset($item['series_data'][$key]);
          }
        }
        // Re-index to handle deleted values.
        $item['series_data'] = array_values($item['series_data']);

        // Go through the series values and massage them.
        foreach ($item['series_data'] as $series_index => &$seriesData) {
          $seriesData = $seriesData[0];

          // Clear settings if changing series type.
          if (isset($form[$this->fieldName]['widget'][$graph_index]['series_data'][$series_index][0]['type']['#default_value']) && $seriesData['type'] != $form[$this->fieldName]['widget'][$graph_index]['series_data'][$series_index][0]['type']['#default_value']) {
            $seriesData = [
              'type' => $seriesData['type'],
            ];
          }

          // Load the template data.
          $plotData = $this->loadExternalPlotlyTemplateFile($seriesData['type']);
          if (isset($plotData['fields'])) {
            // Remove anything that's a default or empty.
            $this->clearUnneededData($seriesData, $this->loadDefaultsFromTemplate($plotData['fields']), []);
            // Properly format these value types.
            $this->massageColorValues($seriesData);
            $this->massageDataArrays($seriesData, $this->loadValueTypesFromTemplate($plotData['fields']), []);
          }
        }

        // Unload blank/default values from the layout.
        // Load the template data.
        $plotData = $this->loadExternalPlotlyTemplateFile('layout');
        // Remove anything that's a default or empty.
        $this->clearUnneededData($item['layout'][0], $this->loadDefaultsFromTemplate($plotData['fields']), []);
        // Properly format these value types.
        $this->massageColorValues($item['layout'][0]);
        $this->massageDataArrays($item['layout'][0], $this->loadValueTypesFromTemplate($plotData['fields']), []);
        // Use the graph name if it hasn't been set in the layout.
        if (!isset($item['layout'][0]['title'])) {
          $item['layout'][0]['title'] = $item['graph_name'];
        }
        // Include the responsive value in the layout array.
        $item['layout'][0]['responsive'] = $item['responsive'];

        // Update item.
        $item = [
          'graph_name' => $item['graph_name'],
          'number_of_series' => $item['number_of_series'],
          'series_data' => serialize($item['series_data']),
          'layout' => serialize($item['layout'][0]),
        ];
      }
    }
    return $values;
  }

  /**
   * Sets color values based on their "use color" options.
   *
   * @param array &$data
   *   The array containing all the submitted values.
   */
  private function massageColorValues(array &$data) {
    foreach ($data as $key => &$value) {
      if (is_array($value)) {
        // If this is a color flag array, process the values.
        if (isset($value['use_' . $key])) {
          // Shift flag into parent.
          $data['use_' . $key] = $value['use_' . $key];
          // If we aren't using the color, remove it (save the flag).
          if ($data['use_' . $key] === 'false') {
            unset($data[$key]);
          }
          // Move subitems up to parent for save.
          else {
            $data[$key] = $value[1];
          }
        }
        else {
          $this->massageColorValues($value);
        }
      }
    }
  }

  /**
   * Clears empty/default data values from settings to save space.
   *
   * @param mixed &$data
   *   The array containing all the data.
   * @param array $defaults
   *   The array containing the default values for all fields in $data.
   * @param array $parents
   *   Array of parent keys for data.
   */
  private function clearUnneededData(&$data, array $defaults, array $parents) {
    foreach ($data as $key => &$value) {
      if (is_array($value)) {
        // Handle multivalue arrays (ajax add more).
        if (isset($value['ajax_actions'])) {
          unset($value['ajax_actions']);
          // Check to see if there are any deleted actions.
          foreach ($value as $ajaxKey => $ajaxValue) {
            if (isset($ajaxValue['delete']) && $ajaxValue['delete'] === 'true') {
              unset($value[$ajaxKey]);
            }
          }
          // If there is anything left, build the array back up.
          if (count($value) > 0) {
            // Re-index to handle deleted values.
            $value = array_values($value);
            // Preserve ajax key.
            $value['ajax_actions'] = ['action'];
          }
        }
        // Clear data for subarray.
        $this->clearUnneededData($value, $defaults, array_merge($parents, [$key]));
        if (count($value) == 0) {
          unset($data[$key]);
        }
      }
      else {
        // Get the default value for this field.
        $thisDefault = $defaults;
        foreach (array_merge($parents, [$key]) as $pKey) {
          if (isset($thisDefault[$pKey])) {
            $thisDefault = $thisDefault[$pKey];
          }
        }

        // Convert ranges to string.
        $matches = [];
        if (preg_match('%\[([0-9\.\-]+,[0-9\.\-]+)\]%', $value, $matches)) {
          $value = $matches[1];
        }

        // Remove blank or default keys.
        if (($value === '' || $value == $thisDefault)) {
          unset($data[$key]);
        }
      }
    }
  }

  /**
   * Converts data arrays from string arrays into actual arrays.
   *
   * @param mixed &$data
   *   The array containing all the data.
   * @param array $valuetypes
   *   The array containing the value types for all fields in $data.
   * @param array $parents
   *   Array of parent keys for data.
   */
  private function massageDataArrays(&$data, array $valuetypes, array $parents) {
    foreach ($data as $key => &$value) {
      if (is_array($value)) {
        // Pass the parent defaults if we don't have one (multivalue arrays).
        $this->massageDataArrays($value, $valuetypes, array_merge($parents, [$key]));
      }
      else {
        // Get the value type for this field.
        $thisValueType = $valuetypes;
        foreach (array_merge($parents, [$key]) as $pKey) {
          if (isset($thisValueType[$pKey])) {
            $thisValueType = $thisValueType[$pKey];
          }
        }
        if ($thisValueType == 'data array' || (($thisValueType == 'number or array of numbers' || $thisValueType == 'integer or array of integers') && !is_numeric($value))) {
          // Break the data arary into array of arrays.
          $value = explode(PHP_EOL, $value);
          // Break the data array string into an actual array.
          foreach ($value as &$subvalue) {
            $subvalue = explode(',', $subvalue);
            foreach ($subvalue as &$entry) {
              $entry = trim($entry);
            }
          }
          // If only one subarray, make it the parent.
          if (count($value) == 1) {
            $value = array_pop($value);
          }
        }
        elseif ($thisValueType == 'array') {
          $value = explode(',', $value);
          // Break the data array string into an actual array.
          foreach ($value as &$subvalue) {
            $subvalue = trim($subvalue);
          }
        }
      }
    }
  }

  /**
   * Loads defaults from a template file.
   *
   * @param array $templateData
   *   The array containing all the template data.
   *
   * @return array
   *   An array of defaults loaded from the template file.
   */
  private function loadDefaultsFromTemplate(array $templateData) {
    $defaults = [];
    foreach ($templateData as $field_data) {
      if (isset($field_data['subfields'])) {
        $defaults[$field_data['fieldname']] = $this->loadDefaultsFromTemplate($field_data['subfields']);
        if (count($defaults[$field_data['fieldname']]) == 0) {
          unset($defaults[$field_data['fieldname']]);
        }
      }
      // Else this is an actual defined field entity.
      elseif (isset($field_data['fieldname']) && isset($field_data['default'])) {
        // Color fields need additional modifying.
        if ($field_data['values'] == 'color') {
          // Convert RBG to hex fi we need to.
          if (substr($field_data['default'], 0, 3) == 'rgb') {
            $field_data['default'] = $this->rgbConvertToHex($field_data['default']);
          }
          // Convert 3 digit hex if we need to.
          elseif (strlen($field_data['default']) == 4) {
            $field_data['default'] = $this->threeHexConvertToHex($field_data['default']);
          }
        }
        $defaults[$field_data['fieldname']] = $field_data['default'];
      }
    }
    return $defaults;
  }

  /**
   * Loads value types from a template file.
   *
   * @param array $templateData
   *   The array containing all the template data.
   *
   * @return array
   *   An array of value types loaded from the template file.
   */
  private function loadValueTypesFromTemplate(array $templateData) {
    $defaults = [];
    foreach ($templateData as $field_data) {
      if (isset($field_data['subfields'])) {
        $defaults[$field_data['fieldname']] = $this->loadValueTypesFromTemplate($field_data['subfields']);
        if (count($defaults[$field_data['fieldname']]) == 0) {
          unset($defaults[$field_data['fieldname']]);
        }
      }
      // Else this is an actual defined field entity.
      elseif (isset($field_data['fieldname']) && isset($field_data['values'])) {
        $defaults[$field_data['fieldname']] = $field_data['values'];
      }
    }
    return $defaults;
  }

  /**
   * {@inheritdoc}
   */
  protected function formMultipleElements(FieldItemListInterface $items, array &$form, FormStateInterface $form_state) {
    $field_name = $this->fieldDefinition->getName();
    $cardinality = $this->fieldDefinition->getFieldStorageDefinition()->getCardinality();
    $parents = $form['#parents'];

    // Determine the number of widgets to display.
    switch ($cardinality) {
      case FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED:
        $field_state = static::getWidgetState($parents, $field_name, $form_state);
        // The only custom modification to this function - we don't show empty.
        $max = max(0, ($field_state['items_count'] - 1));
        $is_multiple = TRUE;
        break;

      default:
        $max = $cardinality - 1;
        $is_multiple = ($cardinality > 1);
        break;
    }

    $title = $this->fieldDefinition->getLabel();
    $description = FieldFilteredMarkup::create($this->token->replace($this->fieldDefinition->getDescription()));

    $elements = [];

    for ($delta = 0; $delta <= $max; $delta++) {
      // Add a new empty item if it doesn't exist yet at this delta.
      if (!isset($items[$delta])) {
        $items->appendItem();
      }

      // For multiple fields, title and description are handled by the wrapping
      // table.
      if ($is_multiple) {
        $element = [
          '#title' => $this->t('@title (value @number)', ['@title' => $title, '@number' => $delta + 1]),
          '#title_display' => 'invisible',
          '#description' => '',
        ];
      }
      else {
        $element = [
          '#title' => $title,
          '#title_display' => 'before',
          '#description' => $description,
        ];
      }

      $element = $this->formSingleElement($items, $delta, $element, $form, $form_state);

      if ($element) {
        // Input field for the delta (drag-n-drop reordering).
        if ($is_multiple) {
          // We name the element '_weight' to avoid clashing with elements
          // defined by widget.
          $element['_weight'] = [
            '#type' => 'weight',
            '#title' => $this->t('Weight for row @number', ['@number' => $delta + 1]),
            '#title_display' => 'invisible',
            // Note: this 'delta' is the FAPI #type 'weight' element's property.
            '#delta' => $max,
            '#default_value' => $items[$delta]->_weight ?: $delta,
            '#weight' => 100,
          ];
        }

        $elements[$delta] = $element;
      }
    }

    if ($elements) {
      $elements += [
        '#theme' => 'field_multiple_value_form',
        '#field_name' => $field_name,
        '#cardinality' => $cardinality,
        '#cardinality_multiple' => $this->fieldDefinition->getFieldStorageDefinition()->isMultiple(),
        '#required' => $this->fieldDefinition->isRequired(),
        '#title' => $title,
        '#description' => $description,
        '#max_delta' => $max,
      ];

      // Add 'add more' button, if not working with a programmed form.
      if ($cardinality == FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED && !$form_state->isProgrammed()) {
        $id_prefix = implode('-', array_merge($parents, [$field_name]));
        $wrapper_id = Html::getUniqueId($id_prefix . '-add-more-wrapper');
        $elements['#prefix'] = '<div id="' . $wrapper_id . '">';
        $elements['#suffix'] = '</div>';

        $elements['add_more'] = [
          '#type' => 'submit',
          '#name' => strtr($id_prefix, '-', '_') . '_add_more',
          '#value' => $this->t('Add another item'),
          '#attributes' => ['class' => ['field-add-more-submit']],
          '#limit_validation_errors' => [array_merge($parents, [$field_name])],
          '#submit' => [[get_class($this), 'addMoreSubmit']],
          '#ajax' => [
            'callback' => [get_class($this), 'addMoreAjax'],
            'wrapper' => $wrapper_id,
            'effect' => 'fade',
          ],
        ];
      }
    }

    return $elements;
  }

}
