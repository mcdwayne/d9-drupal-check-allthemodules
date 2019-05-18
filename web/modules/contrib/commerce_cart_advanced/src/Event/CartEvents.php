<?php

namespace Drupal\commerce_cart_advanced\Event;

/**
 * Defines events for the cart advanced module.
 */
final class CartEvents {

  /**
   * Name of the event fired to split the carts.
   *
   * Fired before the splitting the carts in the CartController.
   *
   * @Event
   *
   * @see \Drupal\commerce_cart_advanced\Event\CartsSplitEvent
   */
  const CARTS_SPLIT = 'commerce_cart_advanced.carts.split';

}
