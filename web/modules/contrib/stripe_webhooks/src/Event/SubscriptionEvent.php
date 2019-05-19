<?php

namespace Drupal\stripe_webhooks\Event;

use Stripe\Customer;
use Stripe\Event as StripeEvent;
use Symfony\Component\EventDispatcher\Event;

/**
 * Defines the subscription event.
 *
 * @see https://stripe.com/docs/api/php#subscription_object
 */
class SubscriptionEvent extends Event {

  /**
   * The subscription.
   *
   * @var \Stripe\Event
   */
  protected $subscription;

  /**
   * Constructs a new SubscriptionEvent.
   *
   * @param \Stripe\Event $subscription
   *   The subscription.
   */
  public function __construct(StripeEvent $subscription) {
    $this->subscription = $subscription;
  }

  /**
   * Gets the owner of the subscription.
   *
   * @return \Stripe\Customer
   *    Returns the owner of the subscription.
   */
  public function getCustomer() {
    return Customer::retrieve($this->subscription->__get('data')['object']['customer']);
  }

  /**
   * Gets the subscription.
   *
   * @return \Stripe\Event
   *   Returns the subscription.
   */
  public function getSubscription() {
    return $this->subscription;
  }

}
