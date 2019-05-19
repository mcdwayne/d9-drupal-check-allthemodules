<?php

namespace Drupal\stripe_webhooks\Event;

use Stripe\Event as StripeEvent;
use Symfony\Component\EventDispatcher\Event;

/**
 * Defines the customer event.
 *
 * @see https://stripe.com/docs/api#customer_object
 */
class CustomerEvent extends Event {

  /**
   * The customer.
   *
   * @var \Stripe\Event
   */
  protected $customer;

  /**
   * Constructs a new CustomerEvent.
   *
   * @param \Stripe\Event $customer
   *   The customer.
   */
  public function __construct(StripeEvent $customer) {
    $this->customer = $customer;
  }

  /**
   * Gets the customer.
   *
   * @return \Stripe\Event
   *   Returns the customer.
   */
  public function getCustomer() {
    return $this->customer;
  }

}
