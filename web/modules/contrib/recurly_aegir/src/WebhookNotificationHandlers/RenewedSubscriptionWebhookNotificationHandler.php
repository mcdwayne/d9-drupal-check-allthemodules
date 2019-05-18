<?php

namespace Drupal\recurly_aegir\WebhookNotificationHandlers;

/**
 * Processes renewed subscriptions.
 */
class RenewedSubscriptionWebhookNotificationHandler extends SubscriptionWebhookNotificationHandler {

  /**
   * {@inheritdoc}
   */
  public function handleNotification() {

    // Set the result and return.
    $this->result = TRUE;
    return $this;
  }

}
