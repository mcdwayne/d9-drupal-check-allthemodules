<?php

namespace Drupal\apitools;

use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Provides the ResponseObject plugin manager.
 */
class ResponseObjectManager extends DefaultPluginManager {

  use DependencySerializationTrait;

  /**
   * @var EntityTypeManagerInterface;
   */
  protected $entityTypeManager;

  /**
   * Constructs a new ApiObjectManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct('Plugin/ApiTools', $namespaces, $module_handler, 'Drupal\apitools\ResponseObjectInterface', 'Drupal\apitools\Annotation\ResponseObject');
    $this->alterInfo('apitools_response_object_info');
    $this->setCacheBackend($cache_backend, 'apitools_response_object_plugins');
    $this->entityTypeManager = $entity_type_manager;
  }

  // TODO:
  // - should turn this into a create function with just values as a param, we can now
  // build the entity because we have all that information
  // then we need a separate load function if we want to create a wrapper with an existing id
  public function createInstance($plugin_id, array $configuration = []) {
    /** @var ResponseObjectInterface $instance */
    $instance = parent::createInstance($plugin_id, $configuration);
    if (!empty($configuration['entity'])) {
      $instance->setEntity($configuration['entity']);
    }
    // Alright for NOW just autocreate the entity here if it doesn't exist from before, and then populate the values
    // We'll treat values and entity separtae for now
    if (!empty($configuration['values'])) {
      $this->initBaseEntity($instance, $configuration);
      $instance->setValues($configuration['values']);
    }
    return $instance;
  }

  public function load($plugin_id, $entity_id) {
    $definition = $this->getDefinition($plugin_id);
    if (empty($definition['base_entity_type'])) {
      return FALSE;
    }
    $entity_type = $definition['base_entity_type'];
    if (!$entity = $this->entityTypeManager->getStorage($entity_type)->load($entity_id)) {
      return FALSE;
    }
    return $this->createInstance($plugin_id, [
      'entity' => $entity,
    ]);
  }

  public function loadByEntity($plugin_id, $entity) {
    return $this->createInstance($plugin_id, [
      'entity' => $entity,
    ]);
  }

  protected function initBaseEntity(ResponseObjectInterface $instance, $configuration) {
    $definition = $instance->getPluginDefinition();
    // If the instance definied a base entity type and it wasn't provided in the parameters.
    if (!empty($definition['base_entity_type']) && !$instance->getEntity()) {
      $entity_type_id = $definition['base_entity_type'];
      $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);
      // Pass all values to the base entity directly.
      $values = $configuration['values'];
      if (!empty($definition['base_entity_bundle'])) {
        $bundle = $definition['base_entity_bundle'];
        $bundle_key = $entity_type->getKey('bundle');
        $values[$bundle_key] = $bundle;
      }
      $entity = $this->entityTypeManager->getStorage($entity_type_id)->create($values);
      $instance->setEntity($entity);
    }
  }
}
