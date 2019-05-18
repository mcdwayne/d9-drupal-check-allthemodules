<?php

namespace Drupal\advertising_products;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Manages advertising products provider plugins.
 */
class AdvertisingProductsProviderManager extends DefaultPluginManager {

  /**
   * Constructs a new AdvertisingProductsProviderManager.
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
    parent::__construct('Plugin/AdvertisingProducts/Provider', $namespaces, $module_handler, 'Drupal\advertising_products\AdvertisingProductsProviderInterface', 'Drupal\advertising_products\Annotation\AdvertisingProductsProvider');
    $this->alterInfo('advertising_products_provider_info');
    $this->setCacheBackend($cache_backend, 'advertising_products_provider');
  }

}
