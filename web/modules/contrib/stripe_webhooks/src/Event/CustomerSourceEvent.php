<?php

namespace Drupal\stripe_webhooks\Event;

use Stripe\Event as StripeEvent;
use Symfony\Component\EventDispatcher\Event;

/**
 * Defines the source event.
 *
 * @see https://stripe.com/docs/api#source_object
 */
class CustomerSourceEvent extends Event {

  /**
   * The customer source.
   *
   * @var \Stripe\Event
   */
  protected $customerSource;

  /**
   * Constructs a new CustomerSourceEvent.
   *
   * @param \Stripe\Event $customer_source
   *   The customer source.
   */
  public function __construct(StripeEvent $customer_source) {
    $this->customerSource = $customer_source;
  }

  /**
   * Gets the customer source.
   *
   * @return \Stripe\Event
   *   Returns the customer source.
   */
  public function getSource() {
    return $this->customerSource;
  }

}
