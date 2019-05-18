<?php

namespace Drupal\braintree_api\Event;

/**
 * Events dispatched by the Braintree API module.
 */
final class BraintreeApiEvents {

  /**
   * Name of the event fired when a new webhook is received from Braintree.
   *
   * @Event
   *
   * @see \Drupal\braintree_api\Event\BraintreeApiWebhookEvent
   *
   * @var string
   */
  const WEBHOOK = 'braintree_api.webhook_notification_received';

}
