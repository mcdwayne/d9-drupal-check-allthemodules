<?php

namespace Drupal\stripe_webhooks\Event;


use Stripe\Event as StripeEvent;
use Symfony\Component\EventDispatcher\Event;

/**
 * Defines the review event.
 *
 * @see https://stripe.com/docs/api#review_object
 */
class ReviewEvent extends Event {

  /**
   * The review.
   *
   * @var \Stripe\Event
   */
  protected $review;

  /**
   * Constructs a new ReviewEvent.
   *
   * @param \Stripe\Event $review
   *   The review.
   */
  public function __construct(StripeEvent $review) {
    $this->review = $review;
  }

  /**
   * Gets the review.
   *
   * @return \Stripe\Event
   *   Returns the review.
   */
  public function getReview() {
    return $this->review;
  }

}
