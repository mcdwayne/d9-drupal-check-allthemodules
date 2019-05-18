<?php

namespace Drupal\mapycz;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\field\FieldStorageConfigInterface;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class MapyCzCore.
 *
 * @package Drupal\mapycz
 */
class MapyCzCore implements ContainerInjectionInterface {

  /**
   * Drupal\Core\Extension\ModuleHandler definition.
   *
   * @var \Drupal\Core\Extension\ModuleHandler
   */
  protected $moduleHandler;

  /**
   * Drupal\Core\Entity\EntityManager definition.
   *
   * @var \Drupal\Core\Entity\EntityManager
   */
  protected $entityManager;

  /**
   * The required configuration object.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   A module handler.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   *   An EntityTypeManager instance.
   * @param \Drupal\Core\Config\ConfigFactory $config
   *   The factory for configuration objects.
   */
  public function __construct(ModuleHandlerInterface $module_handler, EntityTypeManagerInterface $entity_manager, ConfigFactory $config) {
    $this->moduleHandler = $module_handler;
    $this->entityManager = $entity_manager;
    $this->config = $config->get('mapycz.settings');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('module_handler'),
      $container->get('entity_type.manager'),
      $container->get('config.factory')
    );
  }

  /**
   * Get views data for fields and filters.
   *
   * @param \Drupal\field\FieldStorageConfigInterface $field_storage
   *   Storage of the current field.
   *
   * @return array
   *   The data to return to Views.
   */
  public function getViewsFieldData(FieldStorageConfigInterface $field_storage) {
    // Make sure views.views.inc is loaded.
    module_load_include('inc', 'views', 'views.views');

    // Get the default data from the views module.
    $data = views_field_default_views_data($field_storage);

    // Loop through all of the results and set our overrides.
    foreach ($data as $table_name => $table_data) {
      foreach ($table_data as $field_name => $field_data) {
        // Only modify fields.
        if ($field_name != 'delta') {
          if (isset($field_data['field'])) {
            // Use our own field handler.
            $data[$table_name][$field_name]['field']['id'] = 'mapycz_field';
            $data[$table_name][$field_name]['field']['click sortable'] = FALSE;
          }
        }
      }
    }

    return $data;
  }

  /**
   * Builds map object to pass to Twig.
   *
   * @param array $item
   *   Array got from iterating over return value of EntityField->getItems().
   *
   * @return \stdClass
   *   Map object to pass to Twig.
   */
  public static function createMapObject(array $item) {
    $location = $item['raw'];

    $map = new \stdClass();
    $map->lat = $location->lat;
    $map->lng = $location->lng;

    return $map;
  }

  /**
   * Get possible options for map type. Used in selectbox input.
   *
   * @return array
   *   Array of [value => text].
   */
  public static function getMapTypeOptions() {
    return [
      'basic' => 'Základní',
      'satelite' => 'Satelitní',
      'turist' => 'Turistická',
    ];
  }

}
