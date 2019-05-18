<?php

namespace Drupal\recurly_aegir\WebhookNotificationHandlers;

/**
 * Processes successful refund payments.
 */
class SuccessfulRefundPaymentWebhookNotificationHandler extends PaymentWebhookNotificationHandler {

  /**
   * {@inheritdoc}
   */
  public function handleNotification() {

    // Set the result and return.
    $this->result = TRUE;
    return $this;
  }

}
