<?php

namespace Drupal\commerce_inventory_store;

use Drupal\commerce\PurchasableEntityInterface;
use Drupal\commerce_inventory_store\Event\InventoryStoreEvents;
use Drupal\commerce_inventory_store\Event\StoreItemLookupEvent;
use Drupal\commerce_store\Entity\StoreInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Provides an inventory manager for Commerce Store entities.
 */
class InventoryStoreManager {

  /**
   * The Commerce Inventory cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cacheFactory;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * Constructs a new InventoryStoreManager.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_factory
   *   The Commerce Inventory cache backend.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   */
  public function __construct(CacheBackendInterface $cache_factory, EventDispatcherInterface $event_dispatcher) {
    $this->cacheFactory = $cache_factory;
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * Returns a list of Inventory Item and Inventory Location entity IDs.
   *
   * @param \Drupal\commerce\PurchasableEntityInterface $purchasable_entity
   *   The purchasable entity.
   * @param \Drupal\commerce_store\Entity\StoreInterface $store
   *   The store entity.
   *
   * @return array
   *   A list of Inventory Item and Inventory Location entity IDs, ordered by
   *   preferred use.
   */
  public function getStoreItemLocations(PurchasableEntityInterface $purchasable_entity, StoreInterface $store) {
    $cid = implode(':', ['store_items', $purchasable_entity->id(), $store->id()]);

    // Return cached Inventory Item IDs.
    if ($cache = $this->cacheFactory->get($cid)) {
      return $cache->data;
    }

    // Resolve available items.
    /** @var \Drupal\commerce_inventory_store\Event\StoreItemLookupEvent $event */
    $event = $this->eventDispatcher->dispatch(InventoryStoreEvents::STORE_ITEM_LOOKUP, new StoreItemLookupEvent($purchasable_entity, $store));

    // Get inventory IDs.
    $ids = $event->getIds();

    // Compile cache tags.
    $tags = Cache::mergeTags($purchasable_entity->getCacheTagsToInvalidate(), $store->getCacheTagsToInvalidate());
    $tags = Cache::mergeTags($tags, $event->getCacheTags());

    // Set Inventory Item IDs to cache.
    $this->cacheFactory->set($cid, $ids, Cache::PERMANENT, $tags);

    return $ids;
  }

  /**
   * Returns a list of Inventory Item IDs.
   *
   * @param \Drupal\commerce\PurchasableEntityInterface $purchasable_entity
   *   The purchasable entity.
   * @param \Drupal\commerce_store\Entity\StoreInterface $store
   *   The store entity.
   *
   * @return int[]
   *   A list of Inventory Item IDs, ordered by preferred use.
   */
  public function getStoreItemIds(PurchasableEntityInterface $purchasable_entity, StoreInterface $store) {
    return array_column($this->getStoreItemLocations($purchasable_entity, $store), 'item_id');
  }

}
