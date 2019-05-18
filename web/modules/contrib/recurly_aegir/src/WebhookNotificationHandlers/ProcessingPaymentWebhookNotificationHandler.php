<?php

namespace Drupal\recurly_aegir\WebhookNotificationHandlers;

/**
 * Processes payments sent for processing.
 */
class ProcessingPaymentWebhookNotificationHandler extends PaymentWebhookNotificationHandler {

  /**
   * {@inheritdoc}
   */
  public function handleNotification() {

    // Set the result and return.
    $this->result = TRUE;
    return $this;
  }

}
