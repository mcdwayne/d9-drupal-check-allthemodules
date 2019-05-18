<?php

namespace Drupal\odata_client\Plugin;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Provides the Odata auth plugin plugin manager.
 */
class OdataAuthPluginManager extends DefaultPluginManager {

  /**
   * Constructs a new OdataAuthPluginManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces,
    CacheBackendInterface $cache_backend,
    ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/OdataAuthPlugin', $namespaces, $module_handler, 'Drupal\odata_client\Plugin\OdataAuthPluginInterface', 'Drupal\odata_client\Annotation\OdataAuthPlugin');

    $this->alterInfo('odata_client_odata_auth_plugin_info');
    $this->setCacheBackend($cache_backend, 'odata_client_odata_auth_plugin_plugins');
  }

  /**
   * Prepare option list for select element.
   *
   * @return array
   *   The option list.
   */
  public function optionList(): array {
    $option_list = [];
    foreach ($this->getDefinitions() as $key => $definition) {
      $option_list[$key] = $definition['label'];
    }

    return $option_list;
  }

}
