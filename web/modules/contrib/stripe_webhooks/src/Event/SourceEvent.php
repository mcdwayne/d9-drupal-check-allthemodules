<?php

namespace Drupal\stripe_webhooks\Event;

use Stripe\Customer;
use Stripe\Event as StripeEvent;
use Symfony\Component\EventDispatcher\Event;

/**
 * Defines the source event.
 *
 * @see https://stripe.com/docs/api#card_object
 */
class SourceEvent extends Event {

  /**
   * The source.
   *
   * @var \Stripe\Event
   */
  protected $source;

  /**
   * Constructs a new SourceEvent.
   *
   * @param \Stripe\Event $source
   *   The source.
   */
  public function __construct(StripeEvent $source) {
    $this->source = $source;
  }

  /**
   * Gets the owner of the source.
   *
   * @return \Stripe\Customer
   *    Returns the owner of the source.
   */
  public function getCustomer() {
    return Customer::retrieve($this->source->__get('data')['object']['customer']);
  }

  /**
   * Gets the source.
   *
   * @return \Stripe\Event
   *   Returns the source.
   */
  public function getSource() {
    return $this->source;
  }

}
