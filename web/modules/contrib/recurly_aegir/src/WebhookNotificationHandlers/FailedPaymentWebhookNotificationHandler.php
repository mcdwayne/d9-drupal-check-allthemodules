<?php

namespace Drupal\recurly_aegir\WebhookNotificationHandlers;

/**
 * Processes failed payments.
 */
class FailedPaymentWebhookNotificationHandler extends PaymentWebhookNotificationHandler {

  /**
   * {@inheritdoc}
   */
  public function handleNotification() {

    // Set the result and return.
    $this->result = TRUE;
    return $this;
  }

}
