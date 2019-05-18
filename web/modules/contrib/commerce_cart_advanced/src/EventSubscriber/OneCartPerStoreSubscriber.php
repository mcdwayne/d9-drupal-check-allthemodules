<?php

namespace Drupal\commerce_cart_advanced\EventSubscriber;

use Drupal\commerce_cart_advanced\Event\CartEvents;
use Drupal\commerce_cart_advanced\Event\CartsSplitEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber for splitting carts.
 *
 * This is meant to always run before any other event subscribers as it assumes
 * that carts have not yet been split to current and non-curent. Any custom
 * subscribers should be placed to run between this and the
 * MarkedNonCurrentCartSubscriber.
 */
class OneCartPerStoreSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [
      CartEvents::CARTS_SPLIT => ['oneCartPerStore', -50],
    ];
    return $events;
  }

  /**
   * Sets the first cart for each store as current.
   *
   * @param \Drupal\commerce_cart_advanced\Event\CartsSplitEvent $event
   *   The cart advanced split event.
   */
  public function oneCartPerStore(CartsSplitEvent $event) {
    $carts = $event->getCarts();
    $store_carts = [];
    $current_carts = [];

    // Take out one cart per store from the given array.
    foreach ($carts as $cart_id => $cart) {
      $store_id = $cart->getStoreId();
      if (isset($store_carts[$store_id])) {
        continue;
      }

      $store_carts[$store_id] = $cart;
    }

    // We need the result array to be keyed by the cart IDs.
    foreach ($store_carts as $cart) {
      $current_carts[$cart->id()] = $cart;
    }

    $event->setCurrentCarts($current_carts);
    $event->setNonCurrentCarts(array_diff_key($carts, $current_carts));
  }

}
