<?php

namespace Drupal\commerce_inventory_store\Event;

/**
 * Contains all events thrown in the Commerce Inventory Store component.
 */
final class InventoryStoreEvents {

  /**
   * Name of the event fired to find items by a purchasable entity and store.
   *
   * This event is used to return weighted Inventory Item IDs which best fulfill
   * the purchasable entity by store. The event listener method receives a
   * \Drupal\commerce_inventory_store\EventSubscriber\StoreItemLookupEvent
   * instance.
   *
   * @Event
   *
   * @see \Drupal\commerce_inventory_store\Event\StoreItemLookupEvent
   *
   * @var string
   */
  const STORE_ITEM_LOOKUP = 'commerce_inventory_store.store_item_lookup';

}
