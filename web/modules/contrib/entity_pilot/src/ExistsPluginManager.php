<?php

namespace Drupal\entity_pilot;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Defines a plugin manager for plugins that test if an incoming entity exists.
 */
class ExistsPluginManager extends DefaultPluginManager implements ExistsPluginManagerInterface {

  /**
   * Constructs a new ExistsPluginManager.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations,.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/entity_pilot/Exists', $namespaces, $module_handler, 'Drupal\entity_pilot\ExistsPluginInterface', 'Drupal\entity_pilot\Annotation\EntityPilotExists');

    $this->alterInfo('entity_pilot_exists_info');
    $this->setCacheBackend($cache_backend, 'entity_pilot_exists');
  }

  /**
   * {@inheritdoc}
   */
  public function exists(EntityManagerInterface $entity_manager, EntityInterface $entity) {
    foreach ($this->getDefinitions() as $plugin_id => $definition) {
      $instance = $this->createInstance($plugin_id);
      if ($matching_entity = $instance->exists($entity_manager, $entity)) {
        return $matching_entity;
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function preApprove(EntityInterface $incoming, EntityInterface $existing) {
    foreach ($this->getDefinitions() as $plugin_id => $definition) {
      $instance = $this->createInstance($plugin_id);
      $instance->preApprove($incoming, $existing);
    }
  }

}
