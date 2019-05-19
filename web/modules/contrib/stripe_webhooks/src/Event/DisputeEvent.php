<?php

namespace Drupal\stripe_webhooks\Event;

use Stripe\Balance;
use Stripe\Charge;
use Stripe\Event as StripeEvent;
use Symfony\Component\EventDispatcher\Event;

/**
 * Defines the dispute event.
 *
 * @see https://stripe.com/docs/api#dispute_object
 */
class DisputeEvent extends Event {

  /**
   * The dispute.
   *
   * @var \Stripe\Event
   */
  protected $dispute;

  /**
   * Constructs a new AccountEvent.
   *
   * @param \Stripe\Event $dispute
   *   The dispute.
   */
  public function __construct(StripeEvent $dispute) {
    $this->dispute = $dispute;
  }

  /**
   * Gets the balance of the dispute.
   *
   * @return \Stripe\Balance
   *   Returns the balance of the dispute.
   */
  public function getBalance() {
    return Balance::retrieve($this->dispute->__get('data')['object']['balance_transaction']);
  }

  /**
   * Gets the charge of the dispute.
   *
   * @return \Stripe\Charge
   *   Returns the charge of the dispute.
   */
  public function getCharge() {
    return Charge::retrieve($this->dispute->__get('data')['object']['charge']);
  }

  /**
   * Gets the dispute.
   *
   * @return \Stripe\Event
   *   Returns the dispute.
   */
  public function getDispute() {
    return $this->dispute;
  }

}
