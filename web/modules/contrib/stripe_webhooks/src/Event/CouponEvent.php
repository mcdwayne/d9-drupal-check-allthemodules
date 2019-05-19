<?php

namespace Drupal\stripe_webhooks\Event;

use Stripe\Event as StripeEvent;
use Symfony\Component\EventDispatcher\Event;

/**
 * Defines the coupon event.
 *
 * @see https://stripe.com/docs/api#coupon_object
 */
class CouponEvent extends Event {

  /**
   * The coupon.
   *
   * @var \Stripe\Event
   */
  protected $coupon;

  /**
   * Constructs a new CouponEvent.
   *
   * @param \Stripe\Event $coupon
   *   The coupon.
   */
  public function __construct(StripeEvent $coupon) {
    $this->coupon = $coupon;
  }

  /**
   * Gets the coupon.
   *
   * @return \Stripe\Event
   *   Returns the coupon.
   */
  public function getCoupon() {
    return $this->coupon;
  }

}
