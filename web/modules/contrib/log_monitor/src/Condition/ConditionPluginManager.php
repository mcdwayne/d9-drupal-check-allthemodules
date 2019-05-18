<?php

namespace Drupal\log_monitor\Condition;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Provides the Condition plugin plugin manager.
 */
class ConditionPluginManager extends DefaultPluginManager {


  /**
   * Constructs a new ConditionPluginManager object.
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
    parent::__construct('Plugin/log_monitor/Condition', $namespaces, $module_handler, 'Drupal\log_monitor\Condition\ConditionPluginInterface', 'Drupal\log_monitor\Annotation\LogMonitorCondition');

    $this->alterInfo('log_monitor_condition_plugin_info');
    $this->setCacheBackend($cache_backend, 'log_monitor_condition_plugin_plugins');
  }


  public function getFormOptions($filter = '') {
    $options = [];
    foreach($this->getDefinitions() as $plugin_id => $definition) {
      $options[$plugin_id] = (string) $definition['title'];
    }
    return $options;
  }
}
