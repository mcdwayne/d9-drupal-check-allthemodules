<?php

namespace Drupal\connection\Plugin;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Provides the Connection plugin manager.
 */
class ConnectionManager extends DefaultPluginManager {


  /**
   * Constructs a new ConnectionManager object.
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
    parent::__construct(
      'Plugin/Connection',
      $namespaces,
      $module_handler,
      'Drupal\connection\Plugin\ConnectionInterface',
      'Drupal\connection\Annotation\Connection'
    );
    $this->alterInfo('connection_connection_info');
    $this->setCacheBackend($cache_backend, 'connection_connection_plugins');
  }

}
