<?php

namespace Drupal\visualn\Plugin;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Provides the VisualN Drawer Modifier plugin manager.
 */
class VisualNDrawerModifierManager extends DefaultPluginManager {


  /**
   * Constructs a new VisualNDrawerModifierManager object.
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
    parent::__construct('Plugin/VisualN/DrawerModifier', $namespaces, $module_handler, 'Drupal\visualn\Plugin\VisualNDrawerModifierInterface', 'Drupal\visualn\Annotation\VisualNDrawerModifier');

    $this->alterInfo('visualn_drawer_modifier_info');
    $this->setCacheBackend($cache_backend, 'visualn_drawer_modifier_plugins');
  }

}
