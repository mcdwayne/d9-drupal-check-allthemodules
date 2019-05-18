<?php

namespace Drupal\commerce_inventory;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\DependencyInjection\ClassResolverInterface;

/**
 * Provides a manager for determining available quantity of Inventory Items.
 */
class QuantityMinimumManager implements QuantityManagerInterface {

  /**
   * The Commerce Inventory cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cacheFactory;

  /**
   * The class resolver.
   *
   * @var \Drupal\Core\DependencyInjection\ClassResolverInterface
   */
  protected $classResolver;

  /**
   * An array of service ids.
   *
   * @var string[]
   */
  protected $serviceIds;

  /**
   * Constructs a new QuantityOnHandManager.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_factory
   *   The Commerce Inventory cache backend.
   * @param \Drupal\Core\DependencyInjection\ClassResolverInterface $class_resolver
   *   The class resolver.
   * @param string[] $service_ids
   *   An array of service IDs in order.
   */
  public function __construct(CacheBackendInterface $cache_factory, ClassResolverInterface $class_resolver, array $service_ids) {
    $this->cacheFactory = $cache_factory;
    $this->classResolver = $class_resolver;
    $this->serviceIds = $service_ids;
  }

  /**
   * Resolves the Inventory Item's minimum quantity.
   *
   * @param int $inventory_item_id
   *   The Inventory Item entity ID.
   *
   * @return float|int|null
   *   The quantity.
   */
  protected function resolveQuantity($inventory_item_id) {
    // Run through each resolver to determine quantity.
    foreach ($this->serviceIds as $service_id) {
      /** @var \Drupal\commerce_inventory\QuantityMinimumResolverInterface $minimum_resolver */
      $minimum_resolver = $this->classResolver->getInstanceFromDefinition($service_id);
      $quantity = $minimum_resolver->resolve($inventory_item_id);

      // Return quantity if resolved.
      if (is_numeric($quantity)) {
        return $quantity;
      }
    }

    // Default to 0;.
    return 0;
  }

  /**
   * Gets an Inventory Item's quantity available.
   *
   * @param int $inventory_item_id
   *   The Inventory Item entity ID.
   *
   * @return float
   *   The quantity on-hand.
   */
  public function getQuantity($inventory_item_id) {
    $cid = InventoryHelper::generateQuantityCacheId($inventory_item_id, 'minimum');

    // Load on-hand quantity if valid.
    if ($cache = $this->cacheFactory->get($cid)) {
      $cache_data = $cache->data;
    }
    else {
      // Resolve quantity.
      $cache_data['quantity'] = $this->resolveQuantity($inventory_item_id);

      // Set cache tags.
      $cache_tags = InventoryHelper::generateQuantityCacheTags($inventory_item_id);
      $cache_tags = Cache::mergeTags($cache_tags, [$cid]);

      // Set cache.
      $this->cacheFactory->set($cid, $cache_data, Cache::PERMANENT, $cache_tags);
    }

    // Return quantity.
    return floatval($cache_data['quantity']);
  }

  /**
   * {@inheritdoc}
   */
  public function invalidateQuantity($inventory_item_id) {
    $cid = InventoryHelper::generateQuantityCacheId($inventory_item_id, 'minimum');
    $this->cacheFactory->invalidate($cid);
  }

}
