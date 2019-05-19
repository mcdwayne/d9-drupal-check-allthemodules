<?php

namespace Drupal\stripe_webhooks\Event;


use Stripe\Event as StripeEvent;
use Symfony\Component\EventDispatcher\Event;

/**
 * Defines the plan event.
 *
 * @see https://stripe.com/docs/api#plan_object
 */
class PlanEvent extends Event {

  /**
   * The plan.
   *
   * @var \Stripe\Event
   */
  protected $plan;

  /**
   * Constructs a new PlanEvent.
   *
   * @param \Stripe\Event $plan
   *   The plan.
   */
  public function __construct(StripeEvent $plan) {
    $this->plan = $plan;
  }

  /**
   * Gets the plan.
   *
   * @return \Stripe\Event
   *   Returns the plan.
   */
  public function getPlan() {
    return $this->plan;
  }

}
