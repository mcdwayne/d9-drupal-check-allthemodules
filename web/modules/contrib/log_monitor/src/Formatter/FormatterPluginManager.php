<?php

namespace Drupal\log_monitor\Formatter;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Provides the Formatter plugin plugin manager.
 */
class FormatterPluginManager extends DefaultPluginManager {


  /**
   * Constructs a new FormatterPluginManager object.
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
    parent::__construct('Plugin/log_monitor/Formatter', $namespaces, $module_handler, 'Drupal\log_monitor\Formatter\FormatterPluginInterface', 'Drupal\log_monitor\Annotation\LogMonitorFormatter');

    $this->alterInfo('log_monitor_formatter_plugin_info');
    $this->setCacheBackend($cache_backend, 'log_monitor_formatter_plugin_plugins');
  }

}
