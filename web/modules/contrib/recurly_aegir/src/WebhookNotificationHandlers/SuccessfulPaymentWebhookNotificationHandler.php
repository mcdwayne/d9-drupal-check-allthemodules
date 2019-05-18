<?php

namespace Drupal\recurly_aegir\WebhookNotificationHandlers;

/**
 * Processes successful payments.
 */
class SuccessfulPaymentWebhookNotificationHandler extends PaymentWebhookNotificationHandler {

  /**
   * {@inheritdoc}
   */
  public function handleNotification() {

    // Set the result and return.
    $this->result = TRUE;
    return $this;
  }

}
