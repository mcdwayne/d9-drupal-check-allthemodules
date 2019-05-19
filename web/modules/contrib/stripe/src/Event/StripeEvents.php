<?php

namespace Drupal\stripe\Event;

/**
 * Defines events for stripe webhooks.
 * */
final class StripeEvents {

  /**
   * The name of the event fired when a webhook is received.
   *
   * @Event
   */
  const WEBHOOK = 'stripe.webhook';

}
