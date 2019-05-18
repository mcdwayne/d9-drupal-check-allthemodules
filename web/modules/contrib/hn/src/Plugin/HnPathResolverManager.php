<?php

namespace Drupal\hn\Plugin;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Provides the HN Path resolver plugin manager.
 */
class HnPathResolverManager extends DefaultPluginManager {

  /**
   * Constructs a new HnPathResolverManager object.
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
    parent::__construct('Plugin/HnPathResolver', $namespaces, $module_handler, 'Drupal\hn\Plugin\HnPathResolverInterface', 'Drupal\hn\Annotation\HnPathResolver');

    $this->alterInfo('hn_path_resolver_info');
    $this->setCacheBackend($cache_backend, 'hn_path_resolver_plugins');
  }

  /**
   * All plugin instances.
   *
   * @var \Drupal\hn\Plugin\HnPathResolverInterface[]
   */
  private $instances = [];

  /**
   * This loops over all entities until a path resolver returns an entity.
   *
   * @param string $path
   *   The path an entity must be searched for.
   *
   * @return \Drupal\hn\HnPathResolverResponse|null
   *   The entity and response code.
   */
  public function resolve($path) {
    if (!$this->instances) {
      $definition_priorities = [];
      foreach ($this->getDefinitions() as $definition) {
        $this->instances[] = $this->createInstance($definition['id']);
        $definition_priorities[] = $definition['priority'];
      }
      // The plugin with the highest priority must be executed first.
      array_multisort($definition_priorities, SORT_DESC, $this->instances);
    }

    foreach ($this->instances as $plugin) {
      $resolved = $plugin->resolve($path);
      if ($resolved) {
        return $resolved;
      }
    }

    return NULL;
  }

}
