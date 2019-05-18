<?php

namespace Drupal\commerce_inventory\Event;

/**
 * Contains all events thrown in the Commerce Inventory component.
 */
final class CommerceInventoryEvents {

  /**
   * Name of the event fired during inventory quantity-available check.
   *
   * This event is used to return a float amount to alter the current available
   * quantity of an Inventory Item. The event listener method receives a
   * \Drupal\commerce_inventory\EventSubscriber\QuantityAvailabilityEvent
   * instance.
   *
   * @Event
   *
   * @see \Drupal\commerce_inventory\Event\QuantityAvailableEvent
   *
   * @var string
   */
  const QUANTITY_AVAILABLE = 'commerce_inventory.quantity_available';

}
