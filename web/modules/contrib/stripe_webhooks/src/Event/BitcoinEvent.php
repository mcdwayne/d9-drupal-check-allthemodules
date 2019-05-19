<?php

namespace Drupal\stripe_webhooks\Event;

use Stripe\Event as StripeEvent;
use Symfony\Component\EventDispatcher\Event;

/**
 * Defines the bitcoin event.
 *
 * @see https://stripe.com/docs/api/php#bitcoin_receiver_object
 */
class BitcoinEvent extends Event {

  /**
   * The bitcoin.
   *
   * @var \Stripe\Event
   */
  protected $bitcoin;

  /**
   * Constructs a new BitcoinEvent.
   *
   * @param \Stripe\Event $bitcoin
   *   The bitcoin.
   */
  public function __construct(StripeEvent $bitcoin) {
    $this->bitcoin = $bitcoin;
  }

  /**
   * Gets the bitcoin.
   *
   * @return \Stripe\Event
   *   Returns the bitcoin.
   */
  public function getBitcoin() {
    return $this->bitcoin;
  }

}
