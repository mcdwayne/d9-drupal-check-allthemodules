<?php

namespace Drupal\commerce_cart_advanced\EventSubscriber;

use Drupal\commerce_cart_advanced\Event\CartEvents;
use Drupal\commerce_cart_advanced\Event\CartsSplitEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event listener for splitting carts.
 */
class MarkedNonCurrentCartSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [
      CartEvents::CARTS_SPLIT => ['checkMarkedNonCurrent', -100],
    ];
    return $events;
  }

  /**
   * Move all carts flagged as "non_current" to the non current array.
   *
   * @param \Drupal\commerce_cart_advanced\Event\CartsSplitEvent $event
   *   The cart advanced split event.
   */
  public function checkMarkedNonCurrent(CartsSplitEvent $event) {
    $current_carts = $event->getCurrentCarts();
    $non_current_carts = $event->getNonCurrentCarts();

    $changed = FALSE;
    foreach ($current_carts as $key => $current_cart) {
      // Field needs to be non-empty and not FALSE.
      $non_current_field = $current_cart->get(COMMERCE_CART_ADVANCED_NON_CURRENT_FIELD_NAME);
      if ($non_current_field->isEmpty() || !$non_current_field->value) {
        continue;
      }

      // Otherwise, if field exists and is TRUE, move cart to non current.
      $non_current_carts[$key] = $current_cart;
      unset($current_carts[$key]);
      $changed = TRUE;
    }

    if ($changed) {
      $event->setCurrentCarts($current_carts);
      $event->setNonCurrentCarts($non_current_carts);
    }
  }

}
