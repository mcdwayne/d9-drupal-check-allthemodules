<?php

namespace Drupal\stripe_webhooks\Event;

use Stripe\Customer;
use Stripe\Event as StripeEvent;
use Symfony\Component\EventDispatcher\Event;

/**
 * Defines the discount event.
 *
 * @see https://stripe.com/docs/api#discount_object
 */
class DiscountEvent extends Event {

  /**
   * The discount.
   *
   * @var \Stripe\Event
   */
  protected $discount;

  /**
   * Constructs a new DiscountEvent.
   *
   * @param \Stripe\Event $discount
   *   The discount.
   */
  public function __construct(StripeEvent $discount) {
    $this->discount = $discount;
  }

  /**
   * Gets the owner of the applied coupon.
   *
   * @return \Stripe\Customer
   *    Returns the owner of the applied coupon.
   */
  public function getCustomer() {
    return Customer::retrieve($this->discount->__get('data')['object']['customer']);
  }

  /**
   * Gets the discount.
   *
   * @return \Stripe\Event
   *   Returns the discount.
   */
  public function getDiscount() {
    return $this->discount;
  }

}
