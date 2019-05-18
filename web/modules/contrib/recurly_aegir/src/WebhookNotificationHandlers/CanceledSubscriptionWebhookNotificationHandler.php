<?php

namespace Drupal\recurly_aegir\WebhookNotificationHandlers;

/**
 * Processes canceled subscriptions.
 */
class CanceledSubscriptionWebhookNotificationHandler extends SubscriptionWebhookNotificationHandler {

  /**
   * {@inheritdoc}
   */
  public function handleNotification() {

    // Set the result and return.
    $this->result = TRUE;
    return $this;
  }

}
