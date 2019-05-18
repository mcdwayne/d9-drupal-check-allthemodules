<?php

/**
 * @file
 * Contains \Drupal\adv_varnish\UserBlockManager.
 */

namespace Drupal\adv_varnish;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * UserBlock entity manager.
 */
class UserBlockManager extends DefaultPluginManager {

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
    parent::__construct('Plugin/UserBlock', $namespaces, $module_handler, '\Drupal\adv_varnish\UserBlockInterface', 'Drupal\adv_varnish\Annotation\UserBlock');

    $this->alterInfo('adv_varnish_user_block');
    $this->setCacheBackend($cache_backend, 'adv_varnish_user_block');
  }

}
