<?php

namespace Drupal\visualn_views\Plugin\views\style;

use Drupal\rest\Plugin\views\style\Serializer;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\visualn\Manager\DrawerManager;
use Drupal\visualn\Manager\RawResourceFormatManager;

use Symfony\Component\Serializer\SerializerInterface;

use Drupal\core\form\FormStateInterface;
use Drupal\Core\Form\SubformState;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Render\Element;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\visualn\BuilderService;



use Drupal\Core\Render\RenderContext;


/**
 * Style plugin to render listing.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "visualn_drawing",
 *   title = @Translation("VisualN Drawing"),
 *   help = @Translation("Render a listing of view data."),
 *   display_types = { "normal" }
 * )
 *
 */
class VisualNDrawing extends Serializer {

  const RAW_RESOURCE_FORMAT = 'visualn_generic_data_array';

  /**
   * {@inheritdoc}
   */
  protected $usesRowPlugin = FALSE;

  /**
   * {@inheritdoc}
   */
  protected $usesFields = TRUE;

  /**
   * The image style entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $visualNStyleStorage;

  /**
   * The visualn drawer manager service.
   *
   * @var \Drupal\visualn\Manager\DrawerManager
   */
  protected $visualNDrawerManager;

  /**
   * The visualn resource format manager service.
   *
   * @var \Drupal\visualn\Manager\RawResourceFormatManager
   */
  protected $visualNResourceFormatManager;

  /**
   * The visualn builder service.
   *
   * @var \Drupal\visualn\BuilderService
   */
  protected $visualNBuilder;

  /**
   * The visualn unique identifier. Used for fields mapping and html_selector
   *   to distinguish from other drawings.
   *
   * @var string
   */
  protected $vuid;



  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,

      // services used by serializer
      $container->get('serializer'), 
      $container->getParameter('serializer.formats'),
      $container->getParameter('serializer.format_providers'),

      // services used by visauln_drawing itself
      $container->get('entity_type.manager')->getStorage('visualn_style'),
      $container->get('plugin.manager.visualn.drawer'),
      $container->get('plugin.manager.visualn.raw_resource_format'),
      $container->get('visualn.builder')
    );
  }


  /**
   * Constructs a Plugin object.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition,
                              SerializerInterface $serializer, array $serializer_formats, array $serializer_format_providers,
                              EntityStorageInterface $visualn_style_storage, DrawerManager $visualn_drawer_manager, RawResourceFormatManager $visualn_resource_format_manager,
                              BuilderService $visualn_builder) {

    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer, $serializer_formats, $serializer_format_providers);

    //$this->definition = $plugin_definition + $configuration;  // initialized also in parent::_construct()
    $this->visualNStyleStorage = $visualn_style_storage;
    $this->visualNDrawerManager = $visualn_drawer_manager;
    $this->visualNResourceFormatManager = $visualn_resource_format_manager;
    $this->visualNBuilder = $visualn_builder;
  }


  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['visualn_style_id'] = ['default' => ''];
    $options['drawer_config'] = ['default' => []];
    $options['expose_keys_mapping'] = 0;
    $options['drawer_fields'] = ['default' => []];

    return $options;
  }

  /**
   * Get display style options.
   *
   * By default this returns $this->options, but can be overriden
   *   e.g. by exposed keys mapping form.
   *
   * @todo: add to class interface
   */
  public function getVisualNOptions() {
    // @todo: is that ok to change $this->options directly instead of copying and changing a new variable?
    // @todo: check exposed mappings and override if any
    // @todo: rename option key
    if ($this->options['expose_keys_mapping']) {
      // @todo:
      //dsm($this->view->getExposedInput());
      $exposed_input = $this->view->getExposedInput();
      // @todo: the key should be unique. see visualn_form_alter()
      // do not confuse with 'drawer_fields' in buildOptionsForm()
      if (!empty($exposed_input['drawer_fields'])) {
        foreach ($exposed_input['drawer_fields'] as $key => $input_value) {
          // @todo: this one is actually form buildOptionsForm()
          $this->options['drawer_fields'][$key] = $input_value['field'];
        }
      }
    }

    return $this->options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    // hide Serializer option to select format, only json is used to build Resource
    $form['formats']['#access'] = FALSE;

    //$visualn_styles = visualn_style_options(FALSE);
    $visualn_styles = visualn_style_options();
    $description_link = Link::fromTextAndUrl(
      $this->t('Configure VisualN Styles'),
      Url::fromRoute('entity.visualn_style.collection')
    );
    // @see ImageFormatter::settingsForm()
    // @todo: onChange execute an ajax callback to show mappings form for the drawer
    $form['visualn_style_id'] = array(
      '#type' => 'select',
      '#title' => $this->t('VisualN style'),
      '#description' => $this->t('Default style for the data to render.'),
      '#default_value' => $this->options['visualn_style_id'],
      '#options' => $visualn_styles,
      // @todo: add permission check for current user
      '#description' => $description_link->toRenderable() + [
        //'#access' => $this->currentUser->hasPermission('administer visualn styles')
        '#access' => TRUE
      ],
      '#ajax' => [
        'url' => views_ui_build_form_url($form_state),
      ],
      //'#executes_submit_callback' => TRUE,
      '#required' => TRUE,
    );
    $form['drawer_container'] = [
      '#type' => 'container',
      '#process' => [[$this, 'processDrawerContainerSubform']],
    ];

    // @todo: add a checkbox to choose whether to override default drawer config or not
    //    or an option to reset to defaults
    // @todo: add group type of fieldset with info about overriding style drawer config



    $form['expose_keys_mapping'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Expose fields mapping'),
      '#default_value' => $this->options['expose_keys_mapping'],
    ];
  }

  public function processDrawerContainerSubform(array $element, FormStateInterface $form_state, $form) {
    // @todo: it seems that that most code here could be moved outside into a method since it is used multiple times
    //    in other places (see ResourceGenericDrawingFetcher::processDrawerContainerSubform() for example)

    $element_parents = $element['#parents'];

    // Here form_state corresponds to the current display style handler though is not instanceof SubformStateInterface.
    $style_element_parents = array_slice($element['#parents'], 0, -1);
    // since the function if called as a #process callback and the visualn_style_id select was already processed
    // and the values were mapped then it is enough to get form_state value for it and no need to check
    // configuration value (see FormBuilder::processForm() and FormBuilder::doBuildForm())
    // and no need in "is_null($visualn_style_id) then set value from config"
    $visualn_style_id = $form_state->getValue(array_merge($style_element_parents, ['visualn_style_id']));

    // If it is a fresh form (is_null($visualn_style_id)) or an empty option selected ($visualn_style_id == ""),
    // there is nothing to attach for drawer config.
    if (!$visualn_style_id) {
      return $element;
    }


    // Here the drawer plugin is initialized (inside getDrawerPlugin()) with the config stored in the style.
    $drawer_plugin = $this->visualNStyleStorage->load($visualn_style_id)->getDrawerPlugin();

    // We use drawer config from configuration only if it corresponds to the selected style. Also
    // we don't get form_state values for the drawer config here since they are handled by
    // drawer buildConfigurationForm() method itself and also even in buildConfigurationForm()
    // drawer should have access to the $this->configuration['drawer_config'] values.
    if ($visualn_style_id == $this->options['visualn_style_id']) {
      // Set initial configuration for the plugin according to the configuration stored in fetcher config.
      $drawer_config = $this->options['drawer_config'];
      $drawer_plugin->setConfiguration($drawer_config);

      // @todo: uncomment when the issue with handling drawer fields form_state values is resolved.
      //$drawer_fields = $this->options['drawer_fields'];

      // @todo: Until some generic way to hande drawer_fields form is introduced,
      //    e.g. \VisualN::buildDrawerDataKeysForm(), we should handle form_state values for the drawer_fields
      //    manually (i.e. in case of form validation errors form_state values should be used).
      $drawer_fields
        = $form_state->getValue(array_merge($element_parents, ['drawer_fields']), $this->options['drawer_fields']);
    }
    else {
      // Leave drawer_config unset for later initialization with drawer_plugin->getConfiguration() values
      // which are generally taken from visualn style configuration.

      // Initialize drawer_config based on (visualn style stored config) in case it is needed somewhere else below.
      $drawer_config = $drawer_plugin->getConfiguration();

      // Since drawer_fields is always an empty array for a visualn style drawer plugin (VisualNStyle::getDrawerPlugin()),
      // it is ok to set it to an empty array here. In contrast, if null, drawer_config should be taken
      // from the visualn style plugin configuraion.
      $drawer_fields = [];
    }

    // Use unique drawer container key for each visualn style from the select box so that the settings
    // wouldn't be overridden by the previous one on ajax calls (expecially when styles use the same
    // drawer and thus the same configuration form with the same keys).
    $drawer_container_key = $visualn_style_id;


    // get drawer configuration form

    $element[$drawer_container_key]['drawer_config'] = [];
    $element[$drawer_container_key]['drawer_config'] += [
      '#parents' => array_merge($element['#parents'], [$drawer_container_key, 'drawer_config']),
      '#array_parents' => array_merge($element['#array_parents'], [$drawer_container_key, 'drawer_config']),
    ];

    $subform_state = SubformState::createForSubform($element[$drawer_container_key]['drawer_config'], $form, $form_state);
    // attach drawer configuration form
    $element[$drawer_container_key]['drawer_config']
              = $drawer_plugin->buildConfigurationForm($element[$drawer_container_key]['drawer_config'], $subform_state);


    // Process #ajax elements. If drawer configuration form uses #ajax to rebuild elements on cerain events,
    // those calls must use views specific 'url' setting or new elements values won't be saved.
    $this->replaceAjaxOptions($element[$drawer_container_key]['drawer_config'], $form_state);


    // @todo: Use some kind of \VisualN::buildDrawerDataKeysForm($drawer_plugin, $form, $form_state) here.

    // Drawer fields subform (i.e. data_keys mappings) should be attached in a separate #process callback
    // that would trigger after the drawer buildConfigurationForm() attaches the config form
    // and is completed. It is required for the case when drawer has a variable number of data keys.
    // see the code below
    // @see VisualNFormHelper::processDrawerContainerSubform()
    $element[$drawer_container_key]['drawer_fields']['#process'] = [[get_called_class(), 'processDrawerFieldsSubform']];
    $element[$drawer_container_key]['drawer_fields']['#drawer_plugin'] = $drawer_plugin;
    $element[$drawer_container_key]['drawer_fields']['#drawer_fields'] = $drawer_fields;
    $field_names = $this->displayHandler->getFieldLabels();
    $element[$drawer_container_key]['drawer_fields']['#field_names'] = $field_names;


    // @todo: technically, this should be moved to an #after_build callback (though it seems to work even as it is)
    // @see VisualNFormHelper::processDrawerContainerSubform() for more info
    // since drawer and fields onfiguration forms may be empty, do a check (then it souldn't be of details type)
    if (Element::children($element[$drawer_container_key]['drawer_config'])
         || Element::children($element[$drawer_container_key]['drawer_fields'])) {
      $details_open = FALSE;
      if ($form_state->getTriggeringElement()) {
        $style_element_array_parents = array_slice($element['#array_parents'], 0, -1);
        // check that the triggering element is visualn_style_id but not fetcher_id select (or some other element) itself
        $triggering_element = $form_state->getTriggeringElement();
        // @todo: triggering element may be empty
        $details_open = $triggering_element['#array_parents'] === array_merge($style_element_array_parents, ['visualn_style_id']);
        // if triggered an ajaxafield configuration form element, open configuration form details after refresh
        if (!$details_open) {
          $array_merge = array_merge($element['#array_parents'], [$drawer_container_key, 'drawer_config']);
          $array_diff = array_diff($triggering_element['#array_parents'], $array_merge);
          $is_subarray = $triggering_element['#array_parents'] == array_merge($array_merge, $array_diff);
          if ($is_subarray) {
            $details_open = TRUE;
          }
        }
      }
      $element[$drawer_container_key] = [
        '#type' => 'details',
        '#title' => t('Style configuration'),
        '#open' => $details_open,
      ] + $element[$drawer_container_key];
    }


    // @todo: attach #element_validate

    return $element;
  }

  /**
   * Process #ajax elements. If drawer configuration form uses #ajax to rebuild elements on cerain events,
   * those calls must use views specific 'url' setting or new elements values won't be saved.
   */
  protected function replaceAjaxOptions(&$element, FormStateInterface $form_state) {
    foreach (Element::children($element) as $key) {
      if (isset($element[$key]['#ajax'])) {
        $element[$key]['#ajax'] = ['url' => views_ui_build_form_url($form_state)];
      }

      // check subtree elements
      $this->replaceAjaxOptions($element[$key], $form_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitOptionsForm(&$form, FormStateInterface $form_state) {
    parent::submitOptionsForm($form, $form_state);

    //$drawer_container_key = reset(Element::children($form['drawer_container']));
    $drawer_container_key = Element::children($form['drawer_container'])[0];
    //$base_element_parents = array_slice($element_parents, 0, -1);
    $element_parents = array_merge($form['#parents'], ['drawer_container']);
    $base_element_parents = $form['#parents'];



    // Call drawer_plugin submitConfigurationForm(),
    // submitting should be done before $form_state->unsetValue() after restructuring the form_state values, see below.

    $full_form = $form_state->getCompleteForm();
    $subform = $form['drawer_container'][$drawer_container_key]['drawer_config'];
    $sub_form_state = SubformState::createForSubform($subform, $full_form, $form_state);

    $visualn_style_id  = $form_state->getValue(array_merge($base_element_parents, ['visualn_style_id']));
    $visualn_style = $this->visualNStyleStorage->load($visualn_style_id);
    $drawer_plugin = $visualn_style->getDrawerPlugin();
    $drawer_plugin->submitConfigurationForm($subform, $sub_form_state);







    // move drawer_config two levels up (remove 'drawer_container' and $drawer_container_key) in form_state values
    $drawer_config_values = $form_state->getValue(array_merge($element_parents, [$drawer_container_key, 'drawer_config']));
    if (!is_null($drawer_config_values)) {
      $form_state->setValue(array_merge($base_element_parents, ['drawer_config']), $drawer_config_values);
    }

    // move drawer_config two levels up (remove 'drawer_container' and $drawer_container_key) in form_state values
    $drawer_fields_values = $form_state->getValue(array_merge($element_parents, [$drawer_container_key, 'drawer_fields']));
    if (!is_null($drawer_fields_values)) {
      $new_drawer_fields_values = [];
      foreach ($drawer_fields_values as $drawer_field_key => $drawer_field) {
        $new_drawer_fields_values[$drawer_field_key] = $drawer_field['field'];
      }

      $form_state->setValue(array_merge($base_element_parents, ['drawer_fields']), $new_drawer_fields_values);
    }

    // remove remove 'drawer_container' key itself from form_state
    $form_state->unsetValue(array_merge($element_parents, [$drawer_container_key]));
  }




  // @todo: till this point it is mostly a copy-paste from Visualization style plugin
  //    with some changes to ::create() and ::__construct() methods
  //


  /**
   * Prepare drawing complete build
   */
  protected function doDrawingBuild($json_data) {

    // generally this returns $this->options but can overridden
    $style_options = $this->getVisualNOptions();

    // @todo: since this can be cached it could not take style changes (i.e. made in style
    //   configuration interface) into consideration, so a cache tag may be needed.

    $visualn_style_id = $style_options['visualn_style_id'];
    if (empty($visualn_style_id)) {
      return;
    }


    $drawer_config = $style_options['drawer_config'];
    $drawer_fields = $style_options['drawer_fields'];

    $raw_resource_plugin_id = static::RAW_RESOURCE_FORMAT;
    $raw_input = [
      'data' => $json_data,
    ];
    $resource =
      $this->visualNResourceFormatManager->createInstance($raw_resource_plugin_id, [])
      ->buildResource($raw_input);

    // Get drawing build
    $build = $this->visualNBuilder->makeBuildByResource($resource, $visualn_style_id, $drawer_config, $drawer_fields);
    $this->visualNBuilder;

    return $build;
  }


  /**
   * Render JSON markup for further processing into a json array to
   * be used by adapter and optionally shown in views preview
   */
  public function renderJSON() {
    // @note: This is mostly a copy-paste of \Drupal\rest\Plugin\views\style\Serializer::render()
    // but without using row plugin

    $rows = [];

    // If the Data Entity row plugin is used, this will be an array of entities
    // which will pass through Serializer to one of the registered Normalizers,
    // which will transform it to arrays/scalars. If the Data field row plugin
    // is used, $rows will not contain objects and will pass directly to the
    // Encoder.
    foreach ($this->view->result as $row_index => $row) {
      $this->view->row_index = $row_index;
      $rows[] = $this->renderRow($row);
      //$rows[] = $this->view->rowPlugin
        //->render($row);
    }
    unset($this->view->row_index);

    // Get the content type configured in the display or fallback to the
    // default.
    if (empty($this->view->live_preview)) {
      $content_type = !empty($this->options['formats']) ? reset($this->options['formats']) : 'json';
      // @todo: doesn't work in normal view mode
      //$content_type = $this->displayHandler
        //->getContentType();
    }
    else {
      $content_type = !empty($this->options['formats']) ? reset($this->options['formats']) : 'json';
    }
    return $this->serializer
      ->serialize($rows, $content_type, [
      'views_style_plugin' => $this,
    ]);


  }




  /**
   * {@inheritdoc}
   */
  public function render() {
    // @note: The code is based on \Drupal\rest\Plugin\views\display\RestExport::render()

    // @todo: review this method implementation, it is a quickfix
    $renderer = \Drupal::service('renderer');

    $build = [];
    //$build['#markup'] = $this->renderer
    //$build['#markup'] = $this->view->renderer


    $json_markup = $renderer
      ->executeInRenderContext(new RenderContext(), function () {
      return $this->renderJSON();
      //return $this->view->style_plugin
        //->render();
    });

    // decode into an array
    $json_data = json_decode($json_markup, TRUE);

    if (!empty($this->view->live_preview)) {
      $build['json_preview']['#markup'] = $json_markup;
    }

    $drawing_build =  $this->doDrawingBuild($json_data);
    $build['drawing_build'] = $drawing_build;

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  protected function renderRow($row) {
    // @note: The code is based on \Drupal\rest\Plugin\views\row\DataFieldRow::render()

    $output = [];
    foreach ($this->view->field as $id => $field) {

      // @todo: enable raw output option
      // If the raw output option has been set, just get the raw value.
      if (FALSE) {
      //if (!empty($this->rawOutputOptions[$id])) {
        $value = $field
          ->getValue($row);
      }
      else {
        $value = $field
          ->advancedRender($row);
      }

      // Omit excluded fields from the rendered output.
      if (empty($field->options['exclude'])) {
        $output[$id] = $value;
        //$output[$this
          //->getFieldKeyAlias($id)] = $value;
      }
    }
    return $output;
  }

  /**
   * Attach drawer_fields subform based on drawer_plugin dataKeys().
   *
   * The subform is attached in a #process callback to have drawer config form
   * values already mapped at this point. It is needed e.g. for drawers with variable
   * number of data keys managed in a #process callback set in buildConfigurationForm().
   *
   * @see \Drupal\visualn_basic_drawers\Plugin\VisualN\Drawer\LinechartBasicDrawer
   */
  public static function processDrawerFieldsSubform(array $element, FormStateInterface $form_state, $form) {
    // this is mostly a copy-paste of the VisualNFormHelper::processDrawerContainerSubform()
    $field_names = $element['#field_names'];

    $element_parents = $element['#array_parents'];
    $base_element_parents = array_slice($element_parents, 0, -1);
    $base_element_parents[] = 'drawer_config';

    $config_element = NestedArray::getValue($form, $base_element_parents);
    $subform_state = SubformState::createForSubform($config_element, $form, $form_state);

    $drawer_plugin = $element['#drawer_plugin'];
    $drawer_fields = $element['#drawer_fields'];
    $drawer_plugin_clone = clone $drawer_plugin;
    $drawer_config = $drawer_plugin->extractFormValues($config_element, $subform_state);
    $drawer_plugin_clone->setConfiguration($drawer_config);

    $data_keys = $drawer_plugin_clone->dataKeys();
    // @todo: convert textfields into a table in a #process callback
    //    maybe even inside Mapper config form method
    if (!empty($data_keys)) {
      // @todo: get rid of value from 'field' or massage value at plugin submit
      $element += [
        '#type' => 'table',
        '#header' => [t('Data key'), t('Field')],
      ];
      foreach ($data_keys as $i => $data_key) {
        $element[$data_key]['label'] = [
          '#plain_text' => $data_key,
        ];
        $element[$data_key]['field'] = [
          '#type' => 'select',
          '#options' => $field_names,
          '#default_value' => isset($drawer_fields[$data_key]) ? $drawer_fields[$data_key] : '',
          '#empty_option' => t('- Select data source -'),
          '#required' => FALSE,
        ];
      }
    }

    return $element;
  }
}
