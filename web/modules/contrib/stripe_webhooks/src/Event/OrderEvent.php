<?php

namespace Drupal\stripe_webhooks\Event;

use Stripe\Customer;
use Stripe\Event as StripeEvent;
use Symfony\Component\EventDispatcher\Event;

/**
 * Defines the order event.
 *
 * @see https://stripe.com/docs/api#order_object
 */
class OrderEvent extends Event {

  /**
   * The order.
   *
   * @var \Stripe\Event
   */
  protected $order;

  /**
   * Constructs a new OrderEvent.
   *
   * @param \Stripe\Event $order
   *   The order.
   */
  public function __construct(StripeEvent $order) {
    $this->order = $order;
  }

  /**
   * Gets the owner of the order.
   *
   * @return \Stripe\Customer
   *    Returns the owner of the order.
   */
  public function getCustomer() {
    return Customer::retrieve($this->order->__get('data')['object']['customer']);
  }

  /**
   * Gets the order.
   *
   * @return \Stripe\Event
   *   Returns the order.
   */
  public function getOrder() {
    return $this->order;
  }

}
