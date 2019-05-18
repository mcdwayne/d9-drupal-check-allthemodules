<?php

namespace Drupal\global_gateway\SwitcherData;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Provides a plugin manager for global_gateway switcher block items.
 *
 * @see \Drupal\global_gateway\SwitcherData\SwitcherDataPluginManager
 * @see \Drupal\global_gateway\SwitcherData\SwitcherDataInterface
 * @see \Drupal\global_gateway\SwitcherData\SwitcherDataPluginBase
 * @see plugin_api
 */
class SwitcherDataPluginManager extends DefaultPluginManager {

  /**
   * Constructs a new SwitcherDataPluginManager.
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
    parent::__construct('Plugin/global_gateway/switcher_data', $namespaces, $module_handler, 'Drupal\global_gateway\SwitcherData\SwitcherDataPluginInterface', 'Drupal\global_gateway\Annotation\GlobalGatewaySwitcherData');

    $this->alterInfo('global_gateway_switcher_data_info');
    $this->setCacheBackend($cache_backend, 'global_gateway_switcher_data_plugins');
  }

  /**
   * {@inheritdoc}
   */
  public function createInstance($plugin_id, array $configuration = []) {
    // @todo: do not construct if plugin doesn't exist.
    try {
      return $this->getFactory()->createInstance($plugin_id, $configuration);
    }
    catch (PluginNotFoundException $e) {
      return FALSE;
    }
  }

}
