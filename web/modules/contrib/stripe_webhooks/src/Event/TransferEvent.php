<?php

namespace Drupal\stripe_webhooks\Event;

use Stripe\Balance;
use Stripe\Customer;
use Stripe\Event as StripeEvent;
use Symfony\Component\EventDispatcher\Event;

/**
 * Defines the transfer event.
 *
 * @see https://stripe.com/docs/api#transfer_object
 */
class TransferEvent extends Event {

  /**
   * The transfer.
   *
   * @var \Stripe\Event
   */
  protected $transfer;

  /**
   * Constructs a new TransferEvent.
   *
   * @param \Stripe\Event $transfer
   *   The transfer.
   */
  public function __construct(StripeEvent $transfer) {
    $this->transfer = $transfer;
  }

  /**
   * Gets the owner of the applied coupon.
   *
   * @return \Stripe\Customer
   *    Returns the owner of the applied coupon.
   */
  public function getCustomer() {
    return Customer::retrieve($this->transfer->__get('data')['object']['customer']);
  }

  /**
   * Gets the balance of the transfer.
   *
   * @return \Stripe\Balance
   *   Returns the balance of the transfer.
   */
  public function getBalance() {
    return Balance::retrieve($this->transfer->__get('data')['object']['balance_transaction']);
  }

  /**
   * Gets the transfer.
   *
   * @return \Stripe\Event
   *   Returns the transfer.
   */
  public function getTransfer() {
    return $this->transfer;
  }

}
