<?php

namespace Drupal\wsdata\Plugin;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Provides the Wsconnector plugin plugin manager.
 */
class WSConnectorManager extends DefaultPluginManager {

  /**
   * Constructor for WSConnectorManager objects.
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
    parent::__construct('Plugin/WSConnector', $namespaces, $module_handler, 'Drupal\wsdata\Plugin\WSConnectorInterface', 'Drupal\wsdata\Annotation\WSConnector');

    $this->alterInfo('wsdata_wsconnector_info');
    $this->setCacheBackend($cache_backend, 'wsdata_wsconnector_plugins');
  }

}
