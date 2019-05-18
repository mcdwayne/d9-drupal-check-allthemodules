<?php

namespace Drupal\active_cache\Plugin;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Provides the Active cache plugin manager.
 */
class ActiveCacheManager extends DefaultPluginManager {

  protected $instances = [];

  /**
   * Constructor for ActiveCacheManager objects.
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
    parent::__construct('Plugin/ActiveCache', $namespaces, $module_handler, 'Drupal\active_cache\Plugin\ActiveCacheInterface', 'Drupal\active_cache\Annotation\ActiveCache');

    $this->alterInfo('active_cache_active_cache_info');
    $this->setCacheBackend($cache_backend, 'active_cache_active_cache_plugins');
  }

  /**
   * {@inheritdoc}
   * @return \Drupal\active_cache\Plugin\ActiveCacheInterface
   */
  public function getInstance(array $options) {
    $id = $options['id'];

    if (!isset($this->instances[$id])) {
      $this->instances[$id] = FALSE;
      if ($this->hasDefinition($id) && ($instance = $this->createInstance($id))) {
        $this->instances[$id] = $instance;
      }
    }

    return $this->instances[$id];
  }


}
