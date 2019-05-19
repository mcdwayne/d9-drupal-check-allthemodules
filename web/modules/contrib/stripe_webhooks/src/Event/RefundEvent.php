<?php

namespace Drupal\stripe_webhooks\Event;

use Stripe\Charge;
use Stripe\Event as StripeEvent;
use Symfony\Component\EventDispatcher\Event;

/**
 * Defines the refund event.
 *
 * @see https://stripe.com/docs/api#refund_object
 */
class RefundEvent extends Event {

  /**
   * The refund.
   *
   * @var \Stripe\Event
   */
  protected $refund;

  /**
   * Constructs a new RefundEvent.
   *
   * @param \Stripe\Event $refund
   *   The refund.
   */
  public function __construct(StripeEvent $refund) {
    $this->refund = $refund;
  }

  /**
   * Gets the charge of the refund.
   *
   * @return \Stripe\Charge
   *   Returns the charge of the refund.
   */
  public function getCharge() {
    return Charge::retrieve($this->refund->__get('data')['object']['balance_transaction']);
  }

  /**
   * Gets the refund.
   *
   * @return \Stripe\Event
   *   Returns the refund.
   */
  public function getRefund() {
    return $this->refund;
  }

}
