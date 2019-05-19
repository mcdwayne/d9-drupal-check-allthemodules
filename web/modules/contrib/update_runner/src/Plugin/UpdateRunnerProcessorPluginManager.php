<?php

namespace Drupal\update_runner\Plugin;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Provides the update runner processor plugin plugin manager.
 */
class UpdateRunnerProcessorPluginManager extends DefaultPluginManager {

  /**
   * Constructs a new UpdateRunnerProcessorPluginManager object.
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
    parent::__construct('Plugin/UpdateRunnerProcessorPlugin', $namespaces, $module_handler, 'Drupal\Component\Plugin\PluginInspectionInterface', 'Drupal\update_runner\Annotation\UpdateRunnerProcessorPlugin');

    $this->alterInfo('update_runner_update_runner_processor_plugin_info');
    $this->setCacheBackend($cache_backend, 'update_runner_update_runner_processor_plugin_plugins');
  }

}
