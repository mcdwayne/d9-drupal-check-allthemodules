<?php

/**
 * @file
 * Contains \Drupal\adv_varnish\VarnishCacheableEntityManager.
 */

namespace Drupal\adv_varnish;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Cacheable entity manager.
 */
class VarnishCacheableEntityManager extends DefaultPluginManager {

  /**
   * Constructs an ConfigPagesContextManager object.
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
    parent::__construct('Plugin/VarnishCacheableEntity', $namespaces, $module_handler, '\Drupal\adv_varnish\VarnishCacheableEntityInterface', 'Drupal\adv_varnish\Annotation\VarnishCacheableEntity');

    $this->alterInfo('adv_varnish_varnish_cacheable_entity');
    $this->setCacheBackend($cache_backend, 'varnish_cacheable_entity');
  }

}
