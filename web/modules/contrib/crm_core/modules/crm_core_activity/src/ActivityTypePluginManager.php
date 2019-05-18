<?php

namespace Drupal\crm_core_activity;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;

/**
 * Manages activity type plugins.
 *
 * @package Drupal\crm_core_activity
 */
class ActivityTypePluginManager extends DefaultPluginManager {

  /**
   * ActivityTypePluginManager constructor.
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
    parent::__construct('Plugin/crm_core_activity/ActivityType', $namespaces, $module_handler, 'Drupal\crm_core_activity\ActivityTypePluginInterface', 'Drupal\crm_core_activity\Annotation\ActivityTypePlugin');

    $this->alterInfo('activity_type_plugin_info');
    $this->setCacheBackend($cache_backend, 'activity_type_plugins');
  }

}
