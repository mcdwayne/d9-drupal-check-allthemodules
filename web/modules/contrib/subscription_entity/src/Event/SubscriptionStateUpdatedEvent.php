<?php

namespace Drupal\subscription_entity\Event;

use Symfony\Component\EventDispatcher\Event;
use Drupal\subscription_entity\Entity\SubscriptionInterface;

/**
 * Wraps a Subscription entity into an event.
 */
class SubscriptionStateUpdatedEvent extends Event {

  /**
   * Subscription Entity.
   *
   * @var \Drupal\subscription_entity\Entity\SubscriptionInterface
   */
  protected $subscription;

  protected $state;

  /**
   * Adds the subscription object to the event.
   *
   * @param \Drupal\subscription_entity\Entity\SubscriptionInterface $subscription
   *   Subscription Entity.
   * @param int $state
   *   The state of the subscription see subscription.module.
   */
  public function __construct(SubscriptionInterface $subscription, $state) {
    $this->state = $state;
    $this->subscription = $subscription;
  }

  /**
   * Gets the subscription.
   *
   * @return \Drupal\subscription_entity\Entity\SubscriptionInterface
   *   The subscription entity that caused the event to fire.
   */
  public function getSubscription() {
    return $this->subscription;
  }

  /**
   * Gets the subscription state.
   *
   * @return string
   *   The value of the state.
   */
  public function getSubscriptionState() {
    return $this->state;
  }

}
