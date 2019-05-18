<?php

namespace Drupal\recurly_aegir\WebhookNotificationHandlers;

/**
 * Processes void payments.
 */
class VoidPaymentWebhookNotificationHandler extends PaymentWebhookNotificationHandler {

  /**
   * {@inheritdoc}
   */
  public function handleNotification() {

    // Set the result and return.
    $this->result = TRUE;
    return $this;
  }

}
