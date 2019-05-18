<?php

namespace Drupal\better_exposed_filters\Plugin;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Provides the Better exposed filters sort widget plugin manager.
 */
class BetterExposedFiltersSortWidgetManager extends DefaultPluginManager {

  /**
   * Constructs a new BetterExposedFiltersSortWidgetManager object.
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
    parent::__construct('Plugin/better_exposed_filters/sort', $namespaces, $module_handler, 'Drupal\better_exposed_filters\Plugin\BetterExposedFiltersSortWidgetInterface', 'Drupal\better_exposed_filters\Annotation\BetterExposedFiltersSortWidget');

    $this->alterInfo('better_exposed_filters_better_exposed_filters_sort_widget_info');
    $this->setCacheBackend($cache_backend, 'better_exposed_filters_better_exposed_filters_sort_widget_plugins');
  }

}
