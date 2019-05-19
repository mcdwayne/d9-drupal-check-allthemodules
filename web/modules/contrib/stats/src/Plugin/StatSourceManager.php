<?php

namespace Drupal\stats\Plugin;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\stats\StatExecution;

/**
 * Provides the Stat source plugin manager.
 */
class StatSourceManager extends StatPluginManager {

  /**
   * Constructs a new StatSourceManager object.
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
    parent::__construct('Plugin/StatSource', $namespaces, $module_handler, 'Drupal\stats\Plugin\StatSourceInterface', 'Drupal\stats\Annotation\StatSource');

    $this->alterInfo('stats_stat_source_info');
    $this->setCacheBackend($cache_backend, 'stats_stat_source_plugins');
  }

  /**
   * Creates a source plugin instance.
   *
   * @param string $plugin_id
   * @param array $configuration
   * @param \Drupal\stats\StatExecution|NULL $execution
   *
   * @return \Drupal\stats\Plugin\StatSourceInterface
   */
  public function createInstance($plugin_id, array $configuration = [], StatExecution $execution = NULL): StatSourceInterface {
    return parent::createInstance($plugin_id, $configuration, $execution);
  }
}
