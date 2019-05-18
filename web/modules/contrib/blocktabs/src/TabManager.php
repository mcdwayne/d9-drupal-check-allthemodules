<?php

namespace Drupal\blocktabs;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Manages tab plugins.
 *
 * @see hook_tab_info_alter()
 * @see \Drupal\blocktabs\Annotation\Tab
 * @see \Drupal\blocktabs\ConfigurableTabInterface
 * @see \Drupal\blocktabs\ConfigurableTabBase
 * @see \Drupal\blocktabs\TabInterface
 * @see \Drupal\blocktabs\TabBase
 * @see plugin_api
 */
class TabManager extends DefaultPluginManager {

  /**
   * Constructs a new TabManager.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/Tab', $namespaces, $module_handler, 'Drupal\blocktabs\TabInterface', 'Drupal\blocktabs\Annotation\Tab');

    $this->alterInfo('tab_info');
    $this->setCacheBackend($cache_backend, 'tab_plugins');
  }

}
