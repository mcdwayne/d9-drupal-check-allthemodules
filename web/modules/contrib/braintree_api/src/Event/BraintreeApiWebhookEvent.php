<?php

namespace Drupal\braintree_api\Event;

use Braintree\WebhookNotification;
use Symfony\Component\EventDispatcher\Event;

/**
 * Event dispatched when a webhook is received from Braintree.
 */
class BraintreeApiWebhookEvent extends Event {

  /**
   * A string representing the kind of Braintree Webhook.
   *
   * @var string
   */
  protected $kind;

  /**
   * The Braintree Webhook notification object.
   *
   * Object properties depend on the kind of webhook.
   *
   * @var \Braintree\WebhookNotification
   *
   * @see https://developers.braintreepayments.com/reference/general/webhooks/overview#notification-kinds
   */
  protected $webhookNotification;

  /**
   * BraintreeApiWebhookEvent constructor.
   *
   * @param string $kind
   *   A string representing the kind of Braintree Webhook.
   * @param \Braintree\WebhookNotification $webhook_notification
   *   The Braintree Webhook notification object.
   */
  public function __construct($kind, WebhookNotification $webhook_notification) {
    $this->kind = $kind;
    $this->webhookNotification = $webhook_notification;
  }

  /**
   * Gets the Braintree Webhook notification object.
   *
   * @return \Braintree\WebhookNotification
   *   The Braintree Webhook notification object.
   */
  public function getWebhookNotification() {
    return $this->webhookNotification;
  }

  /**
   * Gets the kind of webhook.
   *
   * @return string
   *   The kind of webhook.
   */
  public function getKind() {
    return $this->kind;
  }

}
