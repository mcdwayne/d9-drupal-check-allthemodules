<?php

namespace Drupal\leaflet_countries\Plugin\views\row;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\row\RowPluginBase;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\views\ViewsData;

/**
 * Plugin which formats a row as a country outline.
 *
 * @ViewsRow(
 *   id = "leaflet_country",
 *   title = @Translation("Country outline"),
 *   help = @Translation("Display the row as a leaflet country outline."),
 *   display_types = {"leaflet"},
 * )
 */
class LeafletCountry extends RowPluginBase implements ContainerFactoryPluginInterface {

  /**
   * Overrides Drupal\views\Plugin\Plugin::$usesOptions.
   */
  protected $usesOptions = TRUE;

  /**
   * Does the row plugin support to add fields to it's output.
   *
   * @var bool
   */
  protected $usesFields = TRUE;

  /**
   * The main entity type id for the view base table.
   *
   * @var string
   */
  protected $entityTypeId;

  /**
   * The Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityManager;

  /**
   * The Entity Field manager service property.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The Entity Display Repository service property.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplay;

  /**
   * The View Data service property.
   *
   * @var \Drupal\views\ViewsData
   */
  protected $viewsData;

  /**
   * The Renderer service property.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $renderer;

  /**
   * Constructs a LeafletMap style instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param EntityTypeManagerInterface $entity_manager
   *   The entity manager.
   * @param EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   * @param EntityDisplayRepositoryInterface $entity_display
   *   The entity display manager.
   * @param RendererInterface $renderer
   *   The renderer.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_manager,
    EntityFieldManagerInterface $entity_field_manager,
    EntityDisplayRepositoryInterface $entity_display,
    RendererInterface $renderer,
    ViewsData $view_data
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityManager = $entity_manager;
    $this->entityFieldManager = $entity_field_manager;
    $this->entityDisplay = $entity_display;
    $this->renderer = $renderer;
    $this->viewsData = $view_data;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
      $container->get('entity_display.repository'),
      $container->get('renderer'),
      $container->get('views.views_data')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);
    // First base table should correspond to main entity type.
    $base_table = key($this->view->getBaseTables());
    $views_definition = $this->viewsData->get($base_table);
    $this->entityTypeId = $views_definition['table']['entity type'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    // Get a list of fields and a sublist of geo data fields in this view.
    // @todo use $fields = $this->displayHandler->getFieldLabels();
    $fields = array();
    $fields_geo_data = array();
    foreach ($this->displayHandler->getHandlers('field') as $field_id => $handler) {
      $label = $handler->adminLabel() ?: $field_id;
      $fields[$field_id] = $label;
      if (is_a($handler, 'Drupal\views\Plugin\views\field\EntityField')) {
        $field_storage_definitions = $this->entityFieldManager
          ->getFieldStorageDefinitions($handler->getEntityType());
        $field_storage_definition = $field_storage_definitions[$handler->definition['field_name']];

        if ($field_storage_definition->getType() == 'leaflet_country_item') {
          $fields_geo_data[$field_id] = $label;
        }
      }
    }

    // Check whether we have a geo data field we can work with.
    if (!count($fields_geo_data)) {
      $form['error'] = array(
        '#markup' => $this->t('Please add at least one leaflet country field to the view.'),
      );
      return;
    }

    // Map preset.
    $form['data_source'] = array(
      '#type' => 'select',
      '#title' => $this->t('Data Source'),
      '#description' => $this->t('Which field associates the country?'),
      '#options' => $fields_geo_data,
      '#default_value' => $this->options['data_source'],
      '#required' => TRUE,
      '#weight' => 0,
    );

    // Name field.
    $form['name_field'] = array(
      '#type' => 'select',
      '#title' => $this->t('Title Field'),
      '#description' => $this->t('Choose the field which will appear as a label over the country outline.'),
      '#options' => $fields,
      '#default_value' => $this->options['name_field'],
      '#empty_value' => '',
      '#weight' => 1,
    );

    $form['name_trigger_popup'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Trigger the popup when clicking on the label'),
      '#description' => $this->t('With this option disabled the popup can only be triggered by clicking on the country layer.'),
      '#default_value' => !empty($this->options['name_trigger_popup']) ? $this->options['name_trigger_popup'] : TRUE,
      '#weight' => 2,
    );

    $desc_options = $fields;
    // Add an option to render the entire entity using a view mode.
    if ($this->entityTypeId) {
      $desc_options += array(
        '#rendered_entity' => '<' . $this->t('Rendered @entity entity', array('@entity' => $this->entityTypeId)) . '>',
      );
    }

    $form['description_field'] = array(
      '#type' => 'select',
      '#title' => $this->t('Description Field'),
      '#description' => $this->t('Choose the field or rendering method which will appear as a description in popups.'),
      '#options' => $desc_options,
      '#default_value' => $this->options['description_field'],
      '#empty_value' => '',
      '#weight' => 3,
    );

    if ($this->entityTypeId) {
      // Get the human readable labels for the entity view modes.
      $view_mode_options = array();
      foreach ($this->entityDisplay->getViewModes($this->entityTypeId) as $key => $view_mode) {
        $view_mode_options[$key] = $view_mode['label'];
      }
      // The View Mode drop-down is visible conditional on "#rendered_entity"
      // being selected in the Description drop-down above.
      $form['view_mode'] = array(
        '#type' => 'select',
        '#title' => $this->t('View mode'),
        '#description' => $this->t('View modes are ways of displaying entities.'),
        '#options' => $view_mode_options,
        '#default_value' => !empty($this->options['view_mode']) ? $this->options['view_mode'] : 'full',
        '#states' => array(
          'visible' => array(
            ':input[name="row_options[description_field]"]' => array(
              'value' => '#rendered_entity',
            ),
          ),
        ),
        '#weight' => 5,
      );
    }

    // The outline of a country.
    $form['linecolor'] = array(
      '#type' => 'textfield',
      '#title' => 'Outline color',
      '#description' => $this->t('Enter a hex value for the outline colour.'),
      '#field_prefix' => '#',
      '#size' => 6,
      '#default_value' => $this->options['linecolor'],
      '#empty_value' => '666666',
      '#weight' => 6,
    );

    // The line weight of the line surrounding the country.
    $form['lineweight'] = array(
      '#type' => 'textfield',
      '#title' => 'Weight of the outline',
      '#description' => $this->t('Enter a value like 1 or 1.5'),
      '#size' => 6,
      '#default_value' => $this->options['lineweight'],
      '#empty_value' => '1.5',
      '#weight' => 7,
    );

    // The opacity of the line surrounding the country.
    $form['lineopacity'] = array(
      '#type' => 'textfield',
      '#title' => 'Opacity of the outline',
      '#description' => $this->t('Enter an opacity value from 0 to 1.'),
      '#size' => 6,
      '#default_value' => $this->options['lineopacity'],
      '#empty_value' => '1',
      '#weight' => 7,
    );

    // The hex value for the fill colour.
    $form['fillcolor'] = array(
      '#type' => 'textfield',
      '#title' => 'Fill color',
      '#description' => $this->t('Enter a hex value for the fill colour of a country'),
      '#field_prefix' => '#',
      '#size' => 6,
      '#default_value' => $this->options['fillcolor'],
      '#empty_value' => '666666',
      '#weight' => 8,
    );

    // The opacity value for the fill.
    $form['fillopacity'] = array(
      '#type' => 'textfield',
      '#title' => 'Fill opacity',
      '#description' => $this->t('Enter an opacity value from 0 to 1.'),
      '#size' => 6,
      '#default_value' => $this->options['fillopacity'],
      '#empty_value' => '1',
      '#weight' => 9,
    );

  }

  /**
   * {@inheritdoc}
   */
  public function validateOptionsForm(&$form, FormStateInterface $form_state) {
    $lineopacity = $form_state->getValue(array('row_options', 'lineopacity'));
    $fillopacity = $form_state->getValue(array('row_options', 'fillopacity'));

    if ($lineopacity < 0 || $lineopacity > 1) {
      $form_state->setError($form['lineopacity'], $this->t('Please select an opacity value between 0 and 1'));
    }

    if ($fillopacity < 0 || $fillopacity > 1) {
      $form_state->setError($form['fillopacity'], $this->t('Please select an opacity value between 0 and 1'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function render($row) {
    $code = $this->view->getStyle()->getFieldValue($row->index, $this->options['data_source']);

    if (empty($code)) {
      return FALSE;
    }

    // Load the GeoJSON file.
    $data = json_decode(\Drupal\leaflet_countries\Countries::getIndividualCountryJSON($code), TRUE);
    // Process the GeoJSON data.
    $geojson = array(
      'type' => 'topojson',
      'json' => $data,
    );
    // Prepare the leaflet features.
    return $this->renderLeafletOutlines(array($geojson), $row);
  }

  /**
   * Converts the given list of geo data points into a list of leaflet features.
   *
   * @param array $features
   *   A list of points.
   * @param ResultRow $row
   *   The views result row.
   *
   * @return array
   *   List of leaflet features.
   */
  protected function renderLeafletOutlines($features, ResultRow $row) {
    // Render the entity with the selected view mode.
    $popup_body = '';
    if ($this->options['description_field'] === '#rendered_entity' && is_object($row->_entity)) {
      $entity = $row->_entity;
      $build = $this->entityManager->getViewBuilder($entity->getEntityTypeId())->view($entity, $this->options['view_mode'], $entity->language());
      $popup_body = $this->renderer->render($build);
    }
    // Normal rendering via fields.
    elseif ($this->options['description_field']) {
      $popup_body = $this->view->getStyle()
        ->getField($row->index, $this->options['description_field']);
    }

    $label = $this->view->getStyle()
      ->getField($row->index, $this->options['name_field']);

    foreach ($features as &$feature) {
      $feature['popup'] = $popup_body;
      $feature['label'] = $label;
      $feature['labelTriggerPopup'] = $this->options['name_trigger_popup'];
      $feature['code'] = $this->view->getStyle()->getFieldValue($row->index, $this->options['data_source']);

      $feature['options'] = array(
        'color' => isset($this->options['lineopacity']) ? '#' . $this->options['linecolor'] : '#666666',
        'weight' => isset($this->options['lineweight']) ? $this->options['lineweight'] : '1.5',
        'lineOpacity' => isset($this->options['lineopacity']) ? $this->options['lineopacity'] : '1',
        'fillColor' => isset($this->options['fillcolor']) ? '#' . $this->options['fillcolor'] : '#666666',
        'fillOpacity' => isset($this->options['fillopacity']) ? $this->options['fillopacity'] : '1',
      );

      // Allow sub-classes to adjust the feature.
      $this->alterLeafletFeature($feature, $row);

      // Allow modules to adjust the feature.
      \Drupal::moduleHandler()
        ->alter('leaflet_countries_views_feature', $feature, $row, $this);
    }
    return $features;
  }

  /**
   * Chance for sub-classes to adjust the leaflet feature array.
   *
   * For example, this can be used to add in icon configuration.
   *
   * @param array $feature
   *   The country outline feature.
   * @param ResultRow $row
   *   The Result rows.
   */
  protected function alterLeafletFeature(array &$point, ResultRow $row) {
  }

  /**
   * {@inheritdoc}
   */
  public function validate() {
    $errors = parent::validate();
    // @todo raise validation error if we have no geofield.
    if (empty($this->options['data_source'])) {
      $errors[] = $this->t('Row @row requires the data source to be configured.', array('@row' => $this->definition['title']));
    }
    return $errors;
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['data_source'] = array('default' => '');
    $options['name_field'] = array('default' => '');
    $options['description_field'] = array('default' => '');
    $options['view_mode'] = array('default' => 'teaser');
    $options['linecolor'] = array('default' => '666666');
    $options['lineweight'] = array('default' => '1.5');
    $options['lineopacity'] = array('default' => '1');
    $options['fillopacity'] = array('default' => '1');
    $options['fillcolor'] = array('default' => '666666');

    return $options;
  }
}
