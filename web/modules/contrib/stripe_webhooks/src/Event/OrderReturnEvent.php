<?php

namespace Drupal\stripe_webhooks\Event;

use Stripe\Customer;
use Stripe\Event as StripeEvent;
use Symfony\Component\EventDispatcher\Event;

/**
 * Defines the order return event.
 *
 * @see https://stripe.com/docs/api#order_return_object
 */
class OrderReturnEvent extends Event {

  /**
   * The order return.
   *
   * @var \Stripe\Event
   */
  protected $orderReturn;

  /**
   * Constructs a new OrderReturnEvent.
   *
   * @param \Stripe\Event $order_return
   *   The order return.
   */
  public function __construct(StripeEvent $order_return) {
    $this->orderReturn = $order_return;
  }

  /**
   * Gets the owner of the order return.
   *
   * @return \Stripe\Customer
   *    Returns the owner of the order return.
   */
  public function getCustomer() {
    return Customer::retrieve($this->orderReturn->__get('data')['object']['customer']);
  }

  /**
   * Gets the order return.
   *
   * @return \Stripe\Event
   *   Returns the order return.
   */
  public function getOrderReturn() {
    return $this->orderReturn;
  }

}
