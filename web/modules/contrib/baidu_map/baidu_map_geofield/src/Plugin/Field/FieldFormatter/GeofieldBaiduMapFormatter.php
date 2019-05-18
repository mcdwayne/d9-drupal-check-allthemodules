<?php

namespace Drupal\baidu_map_geofield\Plugin\Field\FieldFormatter;

use Drupal\baidu_map_geofield\GeofieldBaiduMapFieldTrait;
use Drupal\Component\Utility\Html;
use Drupal\Core\Url;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Utility\LinkGeneratorInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\geofield\GeoPHP\GeoPHPInterface;
use Drupal\Core\Render\RendererInterface;

/**
 * Plugin implementation of the 'geofield_baidu_map' formatter.
 *
 * @FieldFormatter(
 *   id = "geofield_baidu_map",
 *   label = @Translation("Geofield Baidu Map"),
 *   field_types = {
 *     "geofield"
 *   }
 * )
 */
class GeofieldBaiduMapFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  use GeofieldBaiduMapFieldTrait;

  /**
   * Empty Map Options.
   *
   * @var array
   */
  protected $emptyMapOptions = [
    '0' => 'Empty field',
    '1' => 'Custom Message',
    '2' => 'Empty Map Centered at the Default Center',
  ];

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * Entity manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Entity display repository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;


  /**
   * The Link generator Service.
   *
   * @var \Drupal\Core\Utility\LinkGeneratorInterface
   */
  protected $link;

  /**
   * The EntityField Manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The geoPhpWrapper service.
   *
   * @var \Drupal\geofield\GeoPHP\GeoPHPInterface
   */
  protected $geoPhpWrapper;

  /**
   * The Renderer service property.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $renderer;

  /**
   * GeofieldGoogleMapFormatter constructor.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings settings.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   A config factory for retrieving required config objects.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The Translation service.
   * @param \Drupal\Core\Utility\LinkGeneratorInterface $link_generator
   *   The Link Generator service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager service.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   *   Entity display repository service.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The Entity Field Manager.
   * @param \Drupal\geofield\GeoPHP\GeoPHPInterface $geophp_wrapper
   *   The The geoPhpWrapper.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The Renderer service.
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    $label,
    $view_mode,
    array $third_party_settings,
    ConfigFactoryInterface $config_factory,
    TranslationInterface $string_translation,
    LinkGeneratorInterface $link_generator,
    EntityTypeManagerInterface $entity_type_manager,
    EntityDisplayRepositoryInterface $entity_display_repository,
    EntityFieldManagerInterface $entity_field_manager,
    GeoPHPInterface $geophp_wrapper,
    RendererInterface $renderer
  ) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->config = $config_factory;
    $this->link = $link_generator;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityDisplayRepository = $entity_display_repository;
    $this->entityFieldManager = $entity_field_manager;
    $this->geoPhpWrapper = $geophp_wrapper;
    $this->renderer = $renderer;
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
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('config.factory'),
      $container->get('string_translation'),
      $container->get('link_generator'),
      $container->get('entity_type.manager'),
      $container->get('entity_display.repository'),
      $container->get('entity_field.manager'),
      $container->get('geofield.geophp'),
      $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return self::getDefaultSettings() + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function getSettings() {
    // Merge defaults before returning the array.
    if (!$this->defaultSettingsMerged) {
      $this->mergeDefaults();
    }
    return $this->settings;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {

    $default_settings = self::defaultSettings();
    $settings = $this->getSettings();

    $elements = $this->generateBmapSettingsForm($form, $form_state, $settings, $default_settings);

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
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {

    // This avoids the infinite loop by stopping the display
    // of any map embedded in an infowindow.
    $view_in_progress = &drupal_static(__FUNCTION__);
    if ($view_in_progress) {
      return [];
    }
    $view_in_progress = TRUE;

    /* @var \Drupal\Core\Entity\EntityInterface $entity */
    $entity = $items->getEntity();
    // Take the entity translation, if existing.
    /* @var \Drupal\Core\TypedData\TranslatableInterface $entity */
    if ($entity->hasTranslation($langcode)) {
      $entity = $entity->getTranslation($langcode);
    }
    $entity_type = $entity->getEntityTypeId();
    $bundle = $entity->bundle();
    $entity_id = $entity->id();
    /* @var \Drupal\Core\Field\FieldDefinitionInterface $field */
    $field = $items->getFieldDefinition();

    $map_settings = $this->getSettings();

    // Performs some preprocess on the maps settings before sending to js.
    $this->preProcessMapSettings($map_settings);

    $js_settings = [
      'mapid' => Html::getUniqueId("baidu_map_geofield_entity_{$bundle}_{$entity_id}_{$field->getName()}"),
      'map_settings' => $map_settings,
      'data' => [],
    ];

    $description_field = isset($map_settings['map_marker_and_infowindow']['infowindow_field']) ? $map_settings['map_marker_and_infowindow']['infowindow_field'] : NULL;
    $description = [];
    // Render the entity with the selected view mode.
    if (isset($description_field) && $description_field === '#rendered_entity' && is_object($entity)) {
      $build = $this->entityTypeManager->getViewBuilder($entity_type)->view($entity, $map_settings['map_marker_and_infowindow']['view_mode']);
      $description[] = $this->renderer->renderRoot($build);
    }
    // Normal rendering via fields.
    elseif (isset($description_field)) {
      $description_field_name = strtolower($map_settings['map_marker_and_infowindow']['infowindow_field']);

      if ($map_settings['map_marker_and_infowindow']['infowindow_field'] === 'title') {
        $description[] = $entity->label();
      }
      elseif (isset($entity->$description_field_name)) {
        foreach ($entity->$description_field_name->getValue() as $value) {
          $description[] = isset($value['value']) ? $value['value'] : '';
          if ($map_settings['map_marker_and_infowindow']['multivalue_split'] == FALSE) {
            break;
          }
        }
      }
    }

    $data = $this->getGeoJsonData($items, $description);

    $js_settings['data'] = [
      'type' => 'FeatureCollection',
      'features' => $data,
    ];

    $element = [baidu_map_geofield_render($js_settings)];

    // Part of infinite loop stopping strategy.
    $view_in_progress = FALSE;

    return $element;
  }

}
