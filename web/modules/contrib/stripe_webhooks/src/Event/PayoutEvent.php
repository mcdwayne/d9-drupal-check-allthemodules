<?php

namespace Drupal\stripe_webhooks\Event;

use Stripe\Balance;
use Stripe\Event as StripeEvent;
use Symfony\Component\EventDispatcher\Event;

/**
 * Defines the payout event.
 *
 * @see https://stripe.com/docs/api#payout_object
 */
class PayoutEvent extends Event {

  /**
   * The payout.
   *
   * @var \Stripe\Event
   */
  protected $payout;

  /**
   * Constructs a new PayoutEvent.
   *
   * @param \Stripe\Event $payout
   *   The payout.
   */
  public function __construct(StripeEvent $payout) {
    $this->payout = $payout;
  }

  /**
   * Gets the payout balance.
   *
   * @return \Stripe\Customer
   *    Returns the payout balance.
   */
  public function getBalance() {
    return Balance::retrieve($this->payout->__get('data')['object']['balance_transaction']);
  }

  /**
   * Gets the payout.
   *
   * @return \Stripe\Event
   *   Returns the payout.
   */
  public function getPayout() {
    return $this->payout;
  }

}
