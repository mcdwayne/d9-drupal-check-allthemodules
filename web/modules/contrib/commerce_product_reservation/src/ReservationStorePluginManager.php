<?php

namespace Drupal\commerce_product_reservation;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * ReservationStore plugin manager.
 */
class ReservationStorePluginManager extends DefaultPluginManager {

  /**
   * Constructs ReservationStorePluginManager object.
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
      'Plugin/ReservationStore',
      $namespaces,
      $module_handler,
      'Drupal\commerce_product_reservation\ReservationStoreInterface',
      'Drupal\commerce_product_reservation\Annotation\ReservationStore'
    );
    $this->alterInfo('reservation_store_info');
    $this->setCacheBackend($cache_backend, 'reservation_store_plugins');
  }

  /**
   * Convenience.
   */
  public function getStoreByStoreProviderAndId($store_provider_id, $store_id) {
    try {
      /** @var \Drupal\commerce_product_reservation\ReservationStoreInterface $store_provider */
      $store_provider = $this->createInstance($store_provider_id);
      foreach ($store_provider->getStores() as $store) {
        if ($store->getId() == $store_id) {
          return $store;
        }
      }
    }
    catch (\Exception $e) {
    }
    return NULL;
  }

}
