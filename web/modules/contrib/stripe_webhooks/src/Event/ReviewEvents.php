<?php

namespace Drupal\stripe_webhooks\Event;

final class ReviewEvents {

  /**
   * Name of the event fired after a review is closed. The review's reason field
   * indicates why (e.g., approved, refunded, refunded_as_fraud, disputed.
   *
   * @Event
   *
   * @see https://stripe.com/docs/api#event_types-review.closed
   */
  const REVIEW_CLOSED = 'stripe.webhooks.review.closed';

  /**
   * Name of the event fired after a review is opened.
   *
   * @Event
   *
   * @see https://stripe.com/docs/api#event_types-review.opened
   */
  const REVIEW_OPENED = 'stripe.webhooks.review.opened';

}
