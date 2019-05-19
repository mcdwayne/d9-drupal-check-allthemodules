<?php

namespace Drupal\stripe_webhooks\Event;

use Stripe\Event as StripeEvent;
use Symfony\Component\EventDispatcher\Event;

/**
 * Defines the balance event.
 *
 * @see https://stripe.com/docs/api#balance_object
 */
class BalanceEvent extends Event {

  /**
   * The balance.
   *
   * @var \Stripe\Event
   */
  protected $balance;

  /**
   * Constructs a new BalanceEvent.
   *
   * @param \Stripe\Event $balance
   *   The balance.
   */
  public function __construct(StripeEvent $balance) {
    $this->balance = $balance;
  }

  /**
   * Gets the balance.
   *
   * @return \Stripe\Event
   *   Returns the balance.
   */
  public function getBalance() {
    return $this->balance;
  }

}
