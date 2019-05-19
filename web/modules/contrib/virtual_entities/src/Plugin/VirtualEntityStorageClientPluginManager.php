<?php

namespace Drupal\virtual_entities\Plugin;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Provides the Virtual entity storage client plugin manager.
 */
class VirtualEntityStorageClientPluginManager extends DefaultPluginManager {

  /**
   * Constructor for VirtualEntityStorageClientPluginManager objects.
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
    parent::__construct('Plugin/VirtualEntityStorageClientPlugin', $namespaces, $module_handler, 'Drupal\virtual_entities\Plugin\VirtualEntityStorageClientPluginInterface', 'Drupal\virtual_entities\Annotation\VirtualEntityStorageClientPlugin');

    $this->alterInfo('virtual_entities_virtual_entity_storage_client_plugin_info');
    $this->setCacheBackend($cache_backend, 'virtual_entities_virtual_entity_storage_client_plugin_plugins');
  }

}
