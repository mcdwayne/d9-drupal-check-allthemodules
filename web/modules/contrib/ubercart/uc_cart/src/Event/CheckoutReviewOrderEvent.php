<?php

namespace Drupal\uc_cart\Event;

use Drupal\uc_order\OrderInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Event that is fired when a customer reviews their order in checkout.
 */
class CheckoutReviewOrderEvent extends Event {

  const EVENT_NAME = 'uc_cart_checkout_review_order';

  /**
   * The order.
   *
   * @var \Drupal\uc_order\OrderInterface
   */
  public $order;

  /**
   * Constructs the object.
   *
   * @param \Drupal\uc_order\OrderInterface $order
   *   The order object.
   */
  public function __construct(OrderInterface $order) {
    $this->order = $order;
  }

}
