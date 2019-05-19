<?php

namespace Drupal\stripe_webhooks\Event;

use Stripe\Balance;
use Stripe\Event as StripeEvent;
use Symfony\Component\EventDispatcher\Event;

/**
 * Defines the charge event.
 *
 * @see https://stripe.com/docs/api#charge_object
 */
class ChargeEvent extends Event {

  /**
   * The charge.
   *
   * @var \Stripe\Event
   */
  protected $charge;

  /**
   * Constructs a new AccountEvent.
   *
   * @param \Stripe\Event $charge
   *   The charge.
   */
  public function __construct(StripeEvent $charge) {
    $this->charge = $charge;
  }

  /**
   * Gets the balance of the charge.
   *
   * @return \Stripe\Balance
   *   Returns the balance of the charge.
   */
  public function getBalance() {
    return Balance::retrieve($this->charge->__get('data')['object']['balance_transaction']);
  }

  /**
   * Gets the charge.
   *
   * @return \Stripe\Event
   *   Returns the charge.
   */
  public function getCharge() {
    return $this->charge;
  }

}
