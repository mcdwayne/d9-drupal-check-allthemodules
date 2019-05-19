<?php

namespace Drupal\watchdog_event_extras;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * A plugin manager for WEE plugins.
 */
class WEEPluginManager extends DefaultPluginManager {

  /**
   * Constructs a WatchdogEventExtrasPluginManager object.
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

    // $subdir = 'Plugin/wee';
    // $plugin_interface = WEEInterface::class;
    // The name of the annotation class that contains the plugin definition.
    // $plugin_definition_annotation_name = WEE::class;.
    parent::__construct(
      'Plugin/WEE',
      $namespaces,
      $module_handler,
      'Drupal\watchdog_event_extras\WEEInterface',
      'Drupal\watchdog_event_extras\Annotation\WEE'
    );

  }

}
