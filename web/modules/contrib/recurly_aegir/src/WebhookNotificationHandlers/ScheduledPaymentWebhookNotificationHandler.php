<?php

namespace Drupal\recurly_aegir\WebhookNotificationHandlers;

/**
 * Processes scheduled payments.
 */
class ScheduledPaymentWebhookNotificationHandler extends PaymentWebhookNotificationHandler {

  /**
   * {@inheritdoc}
   */
  public function handleNotification() {

    // Set the result and return.
    $this->result = TRUE;
    return $this;
  }

}
