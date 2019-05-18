<?php

namespace Drupal\commerce_inventory\EventSubscriber;

use Drupal\commerce_inventory\Event\AdjustQuantityAvailableEvent;
use Drupal\commerce_inventory\Event\CommerceInventoryEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Base class to listen to Inventory Item quantity-available check events.
 */
abstract class AdjustQuantityAvailableSubscriberBase implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[CommerceInventoryEvents::QUANTITY_AVAILABLE] = ['adjustQuantityAvailable'];
    return $events;
  }

  /**
   * Make quantity available adjustments.
   *
   * @param \Drupal\commerce_inventory\Event\AdjustQuantityAvailableEvent $event
   *   The adjust quantity event.
   */
  abstract public function adjustQuantityAvailable(AdjustQuantityAvailableEvent $event);

}
