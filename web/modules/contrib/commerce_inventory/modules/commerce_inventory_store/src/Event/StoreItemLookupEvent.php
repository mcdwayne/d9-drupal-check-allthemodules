<?php

namespace Drupal\commerce_inventory_store\Event;

use Drupal\commerce\PurchasableEntityInterface;
use Drupal\commerce_store\Entity\StoreInterface;
use Drupal\Component\Utility\SortArray;
use Drupal\Core\Cache\Cache;
use Symfony\Component\EventDispatcher\Event;

/**
 * Defines the Store Inventory Item lookup event.
 *
 * @see \Drupal\commerce_inventory\Event\CommerceInventoryEvents
 */
class StoreItemLookupEvent extends Event {

  /**
   * Additional cache tags.
   *
   * @var string[]
   */
  protected $cacheTags = [];

  /**
   * Inventory Item IDs, their Inventory Location IDs and their weights.
   *
   * @var array
   */
  protected $items = [];

  /**
   * The purchasable entity.
   *
   * @var \Drupal\commerce\PurchasableEntityInterface
   */
  protected $purchasableEntity;

  /**
   * The commerce store entity.
   *
   * @var \Drupal\commerce_store\Entity\StoreInterface
   */
  protected $store;

  /**
   * Constructs a StoreItemLookupEvent object.
   *
   * @param \Drupal\commerce\PurchasableEntityInterface $purchasable_entity
   *   The purchasable entity.
   * @param \Drupal\commerce_store\Entity\StoreInterface $store
   *   The commerce store.
   */
  public function __construct(PurchasableEntityInterface $purchasable_entity, StoreInterface $store) {
    $this->purchasableEntity = $purchasable_entity;
    $this->store = $store;
  }

  /**
   * Gets the purchasable entity.
   *
   * @return \Drupal\commerce\PurchasableEntityInterface
   *   The purchasable entity.
   */
  public function getPurchasableEntity() {
    return $this->purchasableEntity;
  }

  /**
   * Gets the Commerce Store.
   *
   * @return \Drupal\commerce_store\Entity\StoreInterface
   *   The Commerce Store entity.
   */
  public function getStore() {
    return $this->store;
  }

  /**
   * Adds additional cache-tags to be tracked.
   *
   * @param string[] $cache_tags
   *   Additional cache tags to track.
   */
  public function addCacheTags(array $cache_tags) {
    $this->cacheTags = Cache::mergeTags($this->cacheTags, $cache_tags);
  }

  /**
   * Returns an array of cache tags related to this event.
   *
   * @return string[]
   *   An array of cache tags.
   */
  public function getCacheTags() {
    // Get cache tags from passed-in items.
    $tags = [];
    foreach ($this->items as $id) {
      $tags[] = 'commerce_inventory_item:' . $id['item_id'];
      $tags[] = 'commerce_inventory_location:' . $id['location_id'];
    }

    // Return combined cache tags with current items.
    return Cache::mergeTags($this->cacheTags, $tags);
  }

  /**
   * Add weighted Inventory Item IDs to the list.
   *
   * @param int $inventory_item_id
   *   The Inventory Item entity ID.
   * @param int $inventory_location_id
   *   The Inventory Location entity ID.
   * @param int $weight
   *   The weight of the location.
   */
  public function addItem(int $inventory_item_id, int $inventory_location_id, $weight = 0) {
    $value = [
      'item_id' => $inventory_item_id,
      'location_id' => $inventory_location_id,
      'weight' => $weight,
    ];

    // Allow overriding previous additions.
    $key = array_search($inventory_item_id, array_column($this->items, 'item_id'));
    if ($key === FALSE) {
      $this->items[] = $value;
    }
    elseif (is_int($key)) {
      $this->items[$key] = $value;
    }
  }

  /**
   * Returns the Inventory Item and Inventory Location IDs ordered by weight.
   *
   * @return array
   *   The array of Inventory Item and Inventory Location IDs ordered by weight.
   */
  public function getIds() {
    $items = $this->items;
    uasort($items, [SortArray::class, 'sortByWeightElement']);
    return $items;
  }

  /**
   * Returns the Inventory Item IDs ordered by weight.
   *
   * @return int[]
   *   The Inventory Item IDs ordered by weight.
   */
  public function getItemIds() {
    $items = $this->items;
    uasort($items, [SortArray::class, 'sortByWeightElement']);
    return array_column($items, 'item_id');
  }

  /**
   * Returns the Inventory Location IDs ordered by weight.
   *
   * @return int[]
   *   The Inventory Location IDs ordered by weight.
   */
  public function getLocationIds() {
    $items = $this->items;
    uasort($items, [SortArray::class, 'sortByWeightElement']);
    return array_column($items, 'location_id');
  }

}
