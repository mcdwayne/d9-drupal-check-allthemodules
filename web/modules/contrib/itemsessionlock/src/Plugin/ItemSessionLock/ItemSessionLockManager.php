<?php

namespace Drupal\itemsessionlock\Plugin\ItemSessionLock;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Item session lock plugin manager.
 */
class ItemSessionLockManager extends DefaultPluginManager {

  /**
   * Constructs an ItemSessionLockManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations,
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/ItemSessionLock', $namespaces, $module_handler, 'Drupal\itemsessionlock\Plugin\ItemSessionLock\ItemSessionLockInterface', 'Drupal\itemsessionlock\Annotation\ItemSessionLock');

    $this->alterInfo('itemsessionlock');
    $this->setCacheBackend($cache_backend, 'itemsessionlock');
  }

}
