<?php

namespace Drupal\baidu_map_geofield\Plugin\Field\FieldWidget;

use Drupal\baidu_map_geofield\GeofieldBaiduMapFieldTrait;
use Drupal\Core\Url;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\geofield\Plugin\Field\FieldWidget\GeofieldLatLonWidget;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Utility\LinkGeneratorInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\geofield\GeoPHP\GeoPHPInterface;
use Drupal\geofield\WktGeneratorInterface;
use Drupal\baidu_map_geofield\leafletTileLayer\LeafletTileLayerPluginManager;
use Drupal\Core\Session\AccountInterface;


/**
 * Plugin implementation of the 'geofield_map' widget.
 *
 * @FieldWidget(
 *   id = "geofield_baidu_map",
 *   label = @Translation("Geofield baidu Map"),
 *   field_types = {
 *     "geofield"
 *   }
 * )
 */
class GeofieldBaiduMapWidget extends GeofieldLatLonWidget implements ContainerFactoryPluginInterface {

  use GeofieldBaiduMapFieldTrait;

  /**
   * The geoPhpWrapper service.
   *
   * @var \Drupal\geofield\GeoPHP\GeoPHPInterface
   */
  protected $geoPhpWrapper;

  /**
   * The WKT format Generator service.
   *
   * @var \Drupal\geofield\WktGeneratorInterface
   */
  protected $wktGenerator;

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * The Link generator Service.
   *
   * @var \Drupal\Core\Utility\LinkGeneratorInterface
   */
  protected $link;

  /**
   * The Renderer service.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;

  /**
   * The EntityField Manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The LeafletTileLayer Manager service.
   *
   * @var \Drupal\baidu_map_geofield\leafletTileLayer\LeafletTileLayerPluginManager
   */
  protected $leafletTileManager;

  /**
   * The Current User.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Lat Lon widget components.
   *
   * @var array
   */
  public $components = ['lon', 'lat'];

  /**
   * Leaflet Map Tile Layers.
   *
   * Free Leaflet Tile Layers from here:
   * http://leaflet-extras.github.io/leaflet-providers/preview/index.html .
   *
   * @var array
   */
  protected $leafletTileLayers;

  /**
   * Leaflet Map Tile Layers Options.
   *
   * @var array
   */
  protected $leafletTileLayersOptions;

  /**
   * GeofieldMapWidget constructor.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param array $third_party_settings
   *   Any third party settings settings.
   * @param \Drupal\geofield\GeoPHP\GeoPHPInterface|null $geophp_wrapper
   *   The geoPhpWrapper.
   * @param \Drupal\geofield\WktGeneratorInterface|null $wkt_generator
   *   The WKT format Generator service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   A config factory for retrieving required config objects.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The Translation service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The Renderer service.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The Entity Field Manager.
   * @param \Drupal\Core\Utility\LinkGeneratorInterface $link_generator
   *   The Link Generator service.
   * @param \Drupal\baidu_map_geofield\leafletTileLayer\LeafletTileLayerPluginManager $leaflet_tile_manager
   *   The LeafletTileLayer Manager service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The Current User.
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    array $third_party_settings,
    GeoPHPInterface $geophp_wrapper,
    WktGeneratorInterface $wkt_generator,
    ConfigFactoryInterface $config_factory,
    TranslationInterface $string_translation,
    RendererInterface $renderer,
    EntityFieldManagerInterface $entity_field_manager,
    LinkGeneratorInterface $link_generator,
    LeafletTileLayerPluginManager $leaflet_tile_manager,
    AccountInterface $current_user
  ) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings, $geophp_wrapper, $wkt_generator);
    $this->config = $config_factory;
    $this->renderer = $renderer;
    $this->entityFieldManager = $entity_field_manager;
    $this->link = $link_generator;
    $this->wktGenerator = $wkt_generator;
    $this->leafletTileManager = $leaflet_tile_manager;
    $this->leafletTileLayers = $this->leafletTileManager->getLeafletTileLayers();
    $this->currentUser = $current_user;
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
      $container->get('geofield.geophp'),
      $container->get('geofield.wkt_generator'),
      $container->get('config.factory'),
      $container->get('string_translation'),
      $container->get('renderer'),
      $container->get('entity_field.manager'),
      $container->get('link_generator'),
      $container->get('plugin.manager.bmap_leaflet_tile_layer_plugin'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
        'map_type' => 'normal',
        'map_dimensions' => [
          'width' => '100%',
          'height' => '450px',
        ],
        'map_style' => [
          'map_style' => 'normal'
        ],
        'baidu_map_geoaddress_field' => [
          'field' => '0',
          'hidden' => FALSE,
          'disabled' => TRUE,
        ],
      ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = [];

    $elements['#tree'] = TRUE;

    $elements['default_value'] = [
      'lat' => [
        '#type' => 'value',
        '#value' => $this->getSetting('default_value')['lat'],
      ],
      'lon' => [
        '#type' => 'value',
        '#value' => $this->getSetting('default_value')['lon'],
      ],
    ];

    $elements = [];
    $settings = $this->getSettings();
    $default_settings = $this->defaultSettings();

    // Set Google Api Key Element.
    $this->setMapBaiduMapApiKeyElement($elements);

    // Set Map Dimension Element.
    $this->setMapDimensionsElement($settings, $elements);

    // Set Map Zoom and Pan Element.
    $this->setMapStyleElement($settings, $default_settings, $elements);
    return $elements;

    $fields_list = array_merge_recursive(
      $this->entityFieldManager->getFieldMapByFieldType('string_long'),
      $this->entityFieldManager->getFieldMapByFieldType('string')
    );

    $string_fields_options = [
      '0' => $this->t('- Any -'),
    ];

    // Filter out the not acceptable values from the options.
    foreach ($fields_list[$form['#entity_type']] as $k => $field) {
      if (in_array(
          $form['#bundle'], $field['bundles']) &&
        !in_array($k, [
          'revision_log',
          'behavior_settings',
          'parent_id',
          'parent_type',
          'parent_field_name',
        ])) {
        $string_fields_options[$k] = $k;
      }
    }

    $elements['baidu_map_geoaddress_field'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Geoaddressed Field'),
      '#description' => $this->t('If a not null Baidu Map API Key is set, it is possible to choose the Entity Title, or a "string" type field (among the content type ones), to sync and populate with the Search / Reverse Geocoded Address.<br><strong> Note: In case of a multivalue Geofield, this is run just from the first Geofield Map</strong>'),
      '#states' => [
        'invisible' => [
          ':input[name="fields[' . $this->fieldDefinition->getName() . '][settings_edit_form][settings][map_google_api_key]"]' => ['value' => ''],
        ],
      ],
    ];
    $elements['baidu_map_geoaddress_field']['field'] = [
      '#type' => 'select',
      '#title' => $this->t('Choose an existing field where to store the Searched / Reverse Geocoded Address'),
      '#description' => $this->t('Choose among the title and the text fields of this entity type, if available'),
      '#options' => $string_fields_options,
      '#default_value' => $this->getSetting('baidu_map_geoaddress_field')['field'],
    ];
    $elements['baidu_map_geoaddress_field']['hidden'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('<strong>Hide</strong> this field in the Content Edit Form'),
      '#description' => $this->t('If checked, the selected Geoaddress Field will be Hidden to the user in the edit form, </br>and totally managed by the Geofield Reverse Geocode'),
      '#default_value' => $this->getSetting('baidu_map_geoaddress_field')['hidden'],
      '#states' => [
        'invisible' => [
          [':input[name="fields[' . $this->fieldDefinition->getName() . '][settings_edit_form][settings][baidu_map_geoaddress_field][field]"]' => ['value' => 'title']],
          'or',
          [':input[name="fields[' . $this->fieldDefinition->getName() . '][settings_edit_form][settings][baidu_map_geoaddress_field][field]"]' => ['value' => '0']],
        ],
      ],
    ];
    $elements['baidu_map_geoaddress_field']['disabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('<strong>Disable</strong> this field in the Content Edit Form'),
      '#description' => $this->t('If checked, the selected Geoaddress Field will be Disabled to the user in the edit form, </br>and totally managed by the Geofield Reverse Geocode'),
      '#default_value' => $this->getSetting('baidu_map_geoaddress_field')['disabled'],
      '#states' => [
        'invisible' => [
          [':input[name="fields[' . $this->fieldDefinition->getName() . '][settings_edit_form][settings][baidu_map_geoaddress_field][hidden]"]' => ['checked' => TRUE]],
          'or',
          [':input[name="fields[' . $this->fieldDefinition->getName() . '][settings_edit_form][settings][baidu_map_geoaddress_field][field]"]' => ['value' => '0']],
        ],
      ],
    ];

    return $elements + parent::settingsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {

    $bmap_api_key = $this->getBaiduMapApiKey();

    // Define the Baidu Maps API Key value message string.
    if (!empty($bmap_api_key)) {
      $state = $this->link->generate($bmap_api_key, Url::fromRoute('baidu_map.settings', [], [
        'query' => [
          'destination' => Url::fromRoute('<current>')
            ->toString(),
        ],
      ]));
    }
    else {
      $state = t("<span class='geofield-baidu-map-warning'>Baidu Map Api Key missing<br>Geocode functionalities not available.</span> @settings_page_link", [
        '@settings_page_link' => $this->link->generate(t('Set it in the Baidu Map Configuration Page'), Url::fromRoute('baidu_map.settings', [], [
          'query' => [
            'destination' => Url::fromRoute('<current>')
              ->toString(),
          ],
        ])),
      ]);
    }

    $bmap_api_key = [
      '#markup' => $this->t('Baidu Map API Key: @state', [
        '@state' => $state,
      ]),
    ];

    $map_type = [
      '#markup' => $this->t('Map Type: @type', [
        '@type' => $this->getSetting('map_type')
      ])
    ];

    $map_dimensions = [
      '#markup' => $this->t('Map Dimensions -'),
    ];

    $map_dimensions['#markup'] .= '<br />' . $this->t('Width: @state;', ['@state' => $this->getSetting('map_dimensions')['width']]);
    $map_dimensions['#markup'] .= '<br />' . $this->t('Height: @state;', ['@state' => $this->getSetting('map_dimensions')['height']]);

    $map_style =[
      '#markup' => $this->t('Map Style -'),
    ];

    $map_settings = $this->getSetting('map_style');
    if ($map_settings['baidu_map_geofield_zoom']) {
      $map_style['#markup'] .= '<br />' . t('Zoom level: @z', array('@z' => $map_settings['baidu_map_geofield_zoom']));
    }
    if ($map_settings['baidu_map_geofield_type']) {
      $map_style['#markup'] .= '<br />' . t('Map Type: @type', array('@type' => ucfirst($map_settings['baidu_map_geofield_type'])));
    }
    if ($map_settings['baidu_map_geofield_style']) {
      $map_style['#markup'] .= '<br />' . t('Map Style: @style', array('@style' => ucfirst($map_settings['baidu_map_geofield_style'])));
    }
    if (isset($map_settings['baidu_map_geofield_showtraffic'])) {
      $map_style['#markup'] .= '<br />' . t('Show traffic: @yn', array('@yn' => ($map_settings['baidu_map_geofield_showtraffic'] ? t('Yes') : t('No'))));
    }
    if (isset($map_settings['baidu_map_geofield_navigationcontrol'])) {
      $map_style['#markup'] .= '<br />' . t('Navigation controls: @yn', array('@yn' => (empty($map_settings['baidu_map_geofield_navigationcontrol']) ? t('Hidden') : ucfirst($map_settings['baidu_map_geofield_navigationcontrol']))));
    }
    if ($map_settings['baidu_map_geofield_scrollwheel']) {
      $map_style['#markup'] .= '<br />' . t('Scrollwheel: @yn', array('@yn' => ($map_settings['baidu_map_geofield_scrollwheel'] ? t('Yes') : t('No'))));
    }
    if ($map_settings['baidu_map_geofield_draggable']) {
      $map_style['#markup'] .= '<br />' . t('Draggable: @yn', array('@yn' => ($map_settings['baidu_map_geofield_draggable'] ? t('Yes') : t('No'))));
    }
    if (isset($map_settings['baidu_map_geofield_maptypecontrol'])) {
      $map_style['#markup'] .= '<br />' . t('Show map type control: @yn', array('@yn' => ($map_settings['baidu_map_geofield_maptypecontrol'] ? t('Yes') : t('No'))));
    }
    if (isset($map_settings['baidu_map_geofield_scalecontrol'])) {
      $map_style['#markup'] .= '<br />' . t('Show the map scale: @yn', array('@yn' => ($map_settings['baidu_map_geofield_scalecontrol'] ? t('Yes') : t('No'))));
    }

    $geoaddress_field_field = [
      '#markup' => $this->t('Geofield -')
    ];
    $geoaddress_field_field['#markup'] .= '<br />' . $this->t('Geoaddress Field: @state', ['@state' => ('0' != $this->getSetting('baidu_map_geoaddress_field')['field']) ? $this->getSetting('baidu_map_geoaddress_field')['field'] : $this->t('- any -')]);
    $geoaddress_field_field['#markup'] .= '<br />' . (('0' != $this->getSetting('baidu_map_geoaddress_field')['field']) ? $this->t('Geoaddress Field Hidden: @state', ['@state' => $this->getSetting('baidu_map_geoaddress_field')['hidden']]) : '');
    $geoaddress_field_field['#markup'] .= '<br />' . (('0' != $this->getSetting('baidu_map_geoaddress_field')['field']) ? $this->t('Geoaddress Field Disabled: @state', ['@state' => $this->getSetting('baidu_map_geoaddress_field')['disabled']]) : '');

    $html5 = [
      '#markup' => $this->t('System - <br />') . $this->t('HTML5 Geolocation button: @state', ['@state' => $this->getSetting('html5_geolocation') ? $this->t('enabled') : $this->t('disabled')]),
    ];

    $summary = [
      'bmap_api_key' => $bmap_api_key,
      'map_type' => $map_type,
      'map_dimensions' => $map_dimensions,
      'map_style' => $map_style,
      'map_geofield' => $geoaddress_field_field,
      'html5' => $html5,
    ];

    return $summary;
  }

  /**
   * Implements \Drupal\field\Plugin\Type\Widget\WidgetInterface::formElement().
   *
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {

    $bmap_api_key = $this->getBaiduMapApiKey();

    $latlon_value = [];

    foreach ($this->components as $component) {
      $latlon_value[$component] = isset($items[$delta]->{$component}) ? floatval($items[$delta]->{$component}) : $this->getSetting('default_value')[$component];
    }

    $element += [
      '#bmap_api_key' => $bmap_api_key,
      '#type' => 'geofield_baidu_map',
      '#default_value' => $latlon_value,
      '#map_type' => $this->getSetting('map_type'),
      '#geolocation' => $this->getSetting('html5_geolocation'),
      '#map_dimensions' => $this->getSetting('map_dimensions'),
      '#map_style' => $this->getSetting('map_style'),
      '#geoaddress_field' => $this->getSetting('baidu_map_geoaddress_field'),
      '#error_label' => !empty($element['#title']) ? $element['#title'] : $this->fieldDefinition->getLabel(),
    ];

    return ['value' => $element];
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    foreach ($values as $delta => $value) {
      foreach ($this->components as $component) {
        if (empty($value['value'][$component]) || !is_numeric($value['value'][$component])) {
          $values[$delta]['value'] = '';
          continue 2;
        }
      }
      $components = $value['value'];
      $values[$delta]['value'] = $this->wktGenerator->wktBuildPoint([$components['lon'], $components['lat']]);
    }

    return $values;
  }

}
