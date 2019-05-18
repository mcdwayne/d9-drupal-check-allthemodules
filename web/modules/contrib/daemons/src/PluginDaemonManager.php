<?php

namespace Drupal\daemons;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Manages daemons plugins.
 *
 * @see plugin_api
 */
class PluginDaemonManager extends DefaultPluginManager {

  /**
   * Constructs a new DaemonManager.
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
    parent::__construct(
      'Plugin/Daemons',
      $namespaces,
      $module_handler,
      'Drupal\daemons\DaemonInterface',
      'Drupal\daemons\Annotation\Daemon'
    );

    $this->alterInfo('daemons_info');
    $this->setCacheBackend($cache_backend, 'daemons_plugins');
  }

}
