<?php

namespace Drupal\reference_map\Plugin;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Provides the Reference Map Type plugin manager.
 */
class ReferenceMapTypeManager extends DefaultPluginManager implements ReferenceMapTypeManagerInterface {

  use StringTranslationTrait;

  /**
   * The Entity Type Manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager = NULL;

  /**
   * The Default Cache Service.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cacheDefault = NULL;

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactory
   */
  protected $logger = NULL;

  /**
   * Constructs a new ReferenceMapTypeManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct(
      'Plugin/ReferenceMapType',
      $namespaces,
      $module_handler,
      'Drupal\reference_map\Plugin\ReferenceMapTypeInterface',
      'Drupal\reference_map\Annotation\ReferenceMapType'
    );

    $this->alterInfo('reference_map_type_info');
    $this->setCacheBackend($cache_backend, 'reference_map_type_plugins');
    $this->entityTypeManager = \Drupal::entityTypeManager();
    $this->cacheDefault = \Drupal::service('cache.default');
    $this->logger = \Drupal::service('logger.factory');
  }

  /**
   * {@inheritdoc}
   */
  public function getInstance(array $options) {
    $plugin_id = $options['plugin_id'];

    if (!$plugin_id) {
      $this->logger->get('default')->error($this->t('No plugin id specified to create Reference Map Type.'));
      return NULL;
    }

    // Try to get the correct plugin definition.
    $configuration['definition'] = $this->getDefinition($plugin_id);

    // Load the config if it is specified.
    if (isset($options['map_id'])) {
      $configuration['config_entity'] = $this->entityTypeManager
        ->getStorage('reference_map_config')
        ->load($options['map_id']);

      // If the plugin couldn't be created using the given options, return null.
      if (!$configuration['config_entity']) {
        $this->logger->get('default')->error($this->t('Unable to create @type map plugin with the id @map_id', [
          '@type' => $options['plugin_id'],
          '@map_id' => $options['map_id'],
        ]));
        return NULL;
      }
    }

    return $this->createInstance($plugin_id, $configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function getMapStepsForEntityType($entity_type_id) {
    $mapped_fields = $this->cacheDefault->get("reference_map_{$entity_type_id}_mapped_fields");

    if (!$mapped_fields) {
      $mapped_fields = [];
      // Get all the maps.
      $maps = $this->entityTypeManager
        ->getStorage('reference_map_config')
        ->loadMultiple();

      // Check in each map to see whether this entity is relevant.
      foreach ($maps as $map_entity) {
        $map_array = $map_entity->map;

        // Add the mapped fields and their respective bundles to the mapped
        // fields array.
        foreach ($map_array as $position => $step) {
          if ($step['entity_type'] === $entity_type_id) {
            $mapped_fields[$map_entity->get('type')][$map_entity->id()][$position] = $step;
          }
        }
      }

      $this->cacheDefault->set("reference_map_{$entity_type_id}_mapped_fields", $mapped_fields);
    }
    else {
      $mapped_fields = $mapped_fields->data;
    }

    // Filter the fields on type.
    if (!empty($type)) {
      return $mapped_fields[$type];
    }

    return $mapped_fields;
  }

  /**
   * {@inheritdoc}
   */
  public function resetMapStepsCache($entity_type_id) {
    $this->cacheDefault->delete("reference_map_{$entity_type_id}_mapped_fields");
  }

}
