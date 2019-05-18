<?php

namespace Drupal\hn\Plugin;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Provides the Headless Ninja Entity Manager Plugin plugin manager.
 */
class HnEntityManagerPluginManager extends DefaultPluginManager {

  /**
   * Constructs a new HnEntityManagerPluginManager object.
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
    parent::__construct('Plugin/HnEntityManagerPlugin', $namespaces, $module_handler, 'Drupal\hn\Plugin\HnEntityManagerPluginInterface', 'Drupal\hn\Annotation\HnEntityManagerPlugin');

    $this->alterInfo('hn_hn_entity_manager_plugin_info');
    $this->setCacheBackend($cache_backend, 'hn_hn_entity_manager_plugin_plugins');
  }

  /**
   * All plugin instances.
   *
   * @var \Drupal\hn\Plugin\HnEntityManagerPluginInterface[]
   */
  private $instances = [];

  /**
   * Gets the entity handler for a given entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity that a handler should be found for.
   *
   * @return \Drupal\hn\Plugin\HnEntityManagerPluginInterface|null
   *   Returns the found handler, or NULL if not found.
   */
  public function getEntityHandler(EntityInterface $entity) {

    if (!$this->instances) {
      $definition_priorities = [];
      foreach ($this->getDefinitions() as $definition) {
        $this->instances[] = $this->createInstance($definition['id']);

        // Default priority = 0
        if (!isset($definition['priority'])) {
          $definition['priority'] = 0;
        }

        $definition_priorities[] = $definition['priority'];
      }
      // The plugin with the highest priority must be executed first.
      array_multisort($definition_priorities, SORT_DESC, $this->instances);
    }
    foreach ($this->instances as $plugin) {
      if ($plugin->isSupported($entity)) {
        return $plugin;
      }
    }

    return NULL;
  }

}
