<?php

namespace Drupal\prometheus_exporter;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * The metrics collector plugin manager.
 */
class MetricsCollectorPluginManager extends DefaultPluginManager {

  /**
   * Constructs a CollectorPluginManager object.
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
    parent::__construct('Plugin/MetricsCollector', $namespaces, $module_handler, 'Drupal\prometheus_exporter\Plugin\MetricsCollectorInterface', 'Drupal\prometheus_exporter\Annotation\MetricsCollector');
    $this->alterInfo('prometheus_exporter_metrics_collector_info');
    $this->setCacheBackend($cache_backend, 'metrics_collectors');
  }

}
