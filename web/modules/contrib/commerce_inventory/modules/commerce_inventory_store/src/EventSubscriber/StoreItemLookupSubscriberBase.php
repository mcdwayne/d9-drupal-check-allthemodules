<?php

namespace Drupal\commerce_inventory_store\EventSubscriber;

use Drupal\commerce_inventory_store\Event\InventoryStoreEvents;
use Drupal\commerce_inventory_store\Event\StoreItemLookupEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Base class to listen to Inventory Item lookup events.
 */
abstract class StoreItemLookupSubscriberBase implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[InventoryStoreEvents::STORE_ITEM_LOOKUP] = ['addItems'];
    return $events;
  }

  /**
   * Lets subscribers add Inventory Item IDs by weight.
   *
   * @param \Drupal\commerce_inventory_store\Event\StoreItemLookupEvent $event
   *   The Store Item Lookup event object.
   */
  abstract public function addItems(StoreItemLookupEvent $event);

}
