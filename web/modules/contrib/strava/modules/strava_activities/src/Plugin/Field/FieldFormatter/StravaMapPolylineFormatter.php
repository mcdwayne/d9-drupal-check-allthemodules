<?php

namespace Drupal\strava_activities\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Utility\LinkGeneratorInterface;
use Drupal\geofield\GeoPHP\GeoPHPInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\geofield_map\Services\GoogleMapsService;
use Drupal\geofield_map\Services\MarkerIconService;
use Drupal\geofield_map\Plugin\Field\FieldFormatter\GeofieldGoogleMapFormatter;
use Polyline;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'strava_map_polyline' formatter.
 *
 * @FieldFormatter(
 *   id = "strava_map_polyline",
 *   label = @Translation("Strava Map Polyline"),
 *   field_types = {
 *     "strava_map_polyline"
 *   }
 * )
 */
class StravaMapPolylineFormatter extends GeofieldGoogleMapFormatter implements ContainerFactoryPluginInterface {

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
   * The Icon Managed File Service.
   *
   * @var \Drupal\geofield_map\Services\MarkerIconService
   */
  protected $markerIcon;

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
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\geofield_map\Services\GoogleMapsService $google_maps_service
   *   The Google Maps service.
   * @param \Drupal\geofield_map\Services\MarkerIconService $marker_icon_service
   *   The Marker Icon Service.
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
    RendererInterface $renderer,
    ModuleHandlerInterface $module_handler,
    GoogleMapsService $google_maps_service,
    MarkerIconService $marker_icon_service
  ) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings, $config_factory, $string_translation, $link_generator, $entity_type_manager, $entity_display_repository, $entity_field_manager, $geophp_wrapper, $renderer, $module_handler, $google_maps_service, $marker_icon_service);
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
      $container->get('renderer'),
      $container->get('module_handler'),
      $container->get('geofield_map.google_maps'),
      $container->get('geofield_map.marker_icon')
    );
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
      'mapid' => Html::getUniqueId("geofield_map_entity_{$bundle}_{$entity_id}_{$field->getName()}"),
      'map_settings' => $map_settings,
      'data' => [],
    ];

    $description = [];
    $description_field = isset($map_settings['map_marker_and_infowindow']['infowindow_field']) ? $map_settings['map_marker_and_infowindow']['infowindow_field'] : NULL;
    /* @var \Drupal\Core\Field\FieldItemList $description_field_entity */
    $description_field_entity = $entity->$description_field;

    // Render the entity with the selected view mode.
    if (isset($description_field) && $description_field === '#rendered_entity' && is_object($entity)) {
      $build = $this->entityTypeManager->getViewBuilder($entity_type)
        ->view($entity, $map_settings['map_marker_and_infowindow']['view_mode']);
      $description[] = $this->renderer->renderPlain($build);
    }
    // Normal rendering via fields.
    elseif (isset($description_field)) {
      if ($map_settings['map_marker_and_infowindow']['infowindow_field'] === 'title') {
        $description[] = $entity->label();
      }
      elseif (isset($entity->$description_field)) {
        $description_field_cardinality = $description_field_entity->getFieldDefinition()
          ->getFieldStorageDefinition()
          ->getCardinality();
        foreach ($description_field_entity->getValue() as $value) {
          $description[] = isset($value['value']) ? $value['value'] : '';
          if ($description_field_cardinality == 1 || $map_settings['map_marker_and_infowindow']['multivalue_split'] == FALSE) {
            break;
          }
        }
      }
    }

    $item = $items->get(0);
    if (is_null($item)) {
      $geojson_data = [];
    }
    else {
      $encoded = $item->getValue();
      $decoded = Polyline::decode($encoded['value']);
      $linestring = $this->coordinatesToLinestring($decoded);
      $start = $this->coordinatesToPoint(array_slice($decoded, 0, 2));
      $end = $this->coordinatesToPoint(array_slice($decoded, -2, 2));

      $geojson_data = $this->getGeoJsonData([
        $start,
        $linestring,
        $end,
      ], $description);
    }

    if (!empty($geojson_data)) {
      $icon_path = \Drupal::service('module_handler')
        ->getModule('strava_activities')
        ->getPath();
      $geojson_data[0]['properties']['icon'] = base_path() . $icon_path . '/images/map-marker-play.png';
      $geojson_data[2]['properties']['icon'] = base_path() . $icon_path . '/images/map-marker-stop.png';
    }

    if (empty($geojson_data) && $map_settings['map_empty']['empty_behaviour'] !== '2') {
      $view_in_progress = FALSE;
      return [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => $map_settings['map_empty']['empty_behaviour'] === '1' ? $map_settings['map_empty']['empty_message'] : '',
        '#attributes' => [
          'class' => ['empty-geofield'],
        ],
      ];
    }
    else {
      $js_settings['data'] = [
        'type' => 'FeatureCollection',
        'features' => $geojson_data,
      ];
    }
    $element = [geofield_map_googlemap_render($js_settings)];

    // Part of infinite loop stopping strategy.
    $view_in_progress = FALSE;

    return $element;
  }

  /**
   * Convert a sequential list of coordinates to a LineString string.
   *
   * @param array $decoded
   *
   * @return string
   */
  private function coordinatesToLinestring(array $decoded) {
    $coordinates = [];
    $lat = NULL;
    $long = NULL;
    foreach ($decoded as $i => $coordinate) {
      if ($i % 2 == 0) {
        $long = $coordinate;
      }
      else {
        $lat = $coordinate;
        $coordinates[] = $lat . ' ' . $long;
      }
    }

    return 'LINESTRING(' . implode(',', $coordinates) . ')';
  }

  /**
   * Convert an array of lat/long coordinates to a Point string.
   *
   * @param array $decoded
   *
   * @return string
   */
  private function coordinatesToPoint(array $decoded) {
    return 'POINT(' . $decoded[1] . ' ' . $decoded[0] . ')';
  }

}
