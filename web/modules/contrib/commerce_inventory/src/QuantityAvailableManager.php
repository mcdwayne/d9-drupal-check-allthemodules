<?php

namespace Drupal\commerce_inventory;

use Drupal\commerce_inventory\Event\AdjustQuantityAvailableEvent;
use Drupal\commerce_inventory\Event\CommerceInventoryEvents;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Provides a manager for determining available quantity of Inventory Items.
 */
class QuantityAvailableManager implements QuantityManagerInterface {

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
   * The quantity on-hand manager.
   *
   * @var \Drupal\commerce_inventory\QuantityManagerInterface
   */
  protected $quantityOnHand;

  /**
   * Constructs a new QuantityOnHandManager.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_factory
   *   The Commerce Inventory cache backend.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   * @param \Drupal\commerce_inventory\QuantityManagerInterface $quantity_on_hand
   *   The quantity on-hand manager.
   */
  public function __construct(CacheBackendInterface $cache_factory, EventDispatcherInterface $event_dispatcher, QuantityManagerInterface $quantity_on_hand) {
    $this->cacheFactory = $cache_factory;
    $this->eventDispatcher = $event_dispatcher;
    $this->quantityOnHand = $quantity_on_hand;
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
    $cid = InventoryHelper::generateQuantityCacheId($inventory_item_id, 'available');

    // Load on-hand quantity if valid.
    if ($cache = $this->cacheFactory->get($cid)) {
      $cache_data = $cache->data;
    }
    else {
      // Get on-hand quantity to adjust.
      $quantity_on_hand = $this->quantityOnHand->getQuantity($inventory_item_id);

      /** @var \Drupal\commerce_inventory\Event\AdjustQuantityAvailableEvent $event */
      $event = $this->eventDispatcher->dispatch(CommerceInventoryEvents::QUANTITY_AVAILABLE, new AdjustQuantityAvailableEvent($inventory_item_id, $quantity_on_hand));

      // Set cache data.
      $cache_data['quantity'] = $event->getQuantityAvailable();
      $cache_data['quantity_on_hand'] = $event->getQuantityOnHand();
      $cache_data['adjustments'] = $event->getQuantityAdjustments();

      // Set cache tags.
      $cache_tags = InventoryHelper::generateQuantityCacheTags($inventory_item_id);
      $cache_tags = Cache::mergeTags($cache_tags, [
        $cid,
        'commerce_inventory_location:' . $event->getInventoryItem()->getLocationId(),
      ]);
      $cache_tags = Cache::mergeTags($cache_tags, $event->getCacheTags());

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
    $cid = InventoryHelper::generateQuantityCacheId($inventory_item_id, 'available');
    $this->cacheFactory->invalidate($cid);
  }

}
