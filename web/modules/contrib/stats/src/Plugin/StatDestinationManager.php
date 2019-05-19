<?php

namespace Drupal\stats\Plugin;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\stats\StatExecution;

/**
 * Provides the Stat destination plugin manager.
 */
class StatDestinationManager extends StatPluginManager {


  /**
   * Constructs a new StatDestinationManager object.
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
    parent::__construct('Plugin/StatDestination', $namespaces, $module_handler, 'Drupal\stats\Plugin\StatDestinationInterface', 'Drupal\stats\Annotation\StatDestination');

    $this->alterInfo('stats_stat_destination_info');
    $this->setCacheBackend($cache_backend, 'stats_stat_destination_plugins');
  }

  /**
   * Creates a destination plugin instance.
   *
   * @param string $plugin_id
   * @param array $configuration
   * @param \Drupal\stats\StatExecution|NULL $execution
   *
   * @return \Drupal\stats\Plugin\StatDestinationInterface
   */
  public function createInstance($plugin_id, array $configuration = [], StatExecution $execution = NULL): StatDestinationInterface {
    return parent::createInstance($plugin_id, $configuration, $execution);
  }

}
