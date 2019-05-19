<?php

namespace Drupal\stripe_webform\Event;

/**
 * Defines events for stripe webhooks
 * */
final class StripeWebformEvents {

  /**
   * The name of the event fired when a webhook is received
   *
   * @Event
   */
  const WEBHOOK = 'stripe_webform.webhook';
}
