<?php

namespace Drupal\log_monitor\Scheduler;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Provides the Scheduler plugin plugin manager.
 */
class SchedulerPluginManager extends DefaultPluginManager {


  /**
   * Constructs a new SchedulerPluginManager object.
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
    parent::__construct('Plugin/log_monitor/Scheduler', $namespaces, $module_handler, 'Drupal\log_monitor\Scheduler\SchedulerPluginInterface', 'Drupal\log_monitor\Annotation\LogMonitorScheduler');

    $this->alterInfo('log_monitor_scheduler_plugin_info');
    $this->setCacheBackend($cache_backend, 'log_monitor_scheduler_plugin_plugins');
  }

}
