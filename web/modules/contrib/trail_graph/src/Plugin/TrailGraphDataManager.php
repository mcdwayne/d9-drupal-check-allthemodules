<?php

namespace Drupal\trail_graph\Plugin;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Provides the Trail graph data plugin manager.
 */
class TrailGraphDataManager extends DefaultPluginManager {

  /**
   * Constructs a new TrailGraphDataManager object.
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
    parent::__construct('Plugin/TrailGraphData', $namespaces, $module_handler, 'Drupal\trail_graph\Plugin\TrailGraphDataInterface', 'Drupal\trail_graph\Annotation\TrailGraphData');

    $this->alterInfo('trail_graph_trail_graph_data_info');
    $this->setCacheBackend($cache_backend, 'trail_graph_trail_graph_data_plugins');
  }

}
