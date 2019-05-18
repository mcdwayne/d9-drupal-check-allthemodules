<?php

namespace Drupal\commerce_swisscom_easypay\Event;

/**
 * Defines events for the Commerce Swisscom Easypay payment gateway.
 *
 * @package Drupal\commerce_swisscom_easypay\Event
 */
class SwisscomEasypayEvents {

  /**
   * Event fired after the checkout page item has been build.
   *
   * This allows to alter any data for the checkout page.
   *
   * @Event
   *
   * @see \Drupal\commerce_swisscom_easypay\Event\CheckoutPageItemEvent
   */
  const CHECKOUT_PAGE_ITEM = 'commerce_swisscom_easypay.checkout_page_item';

}
