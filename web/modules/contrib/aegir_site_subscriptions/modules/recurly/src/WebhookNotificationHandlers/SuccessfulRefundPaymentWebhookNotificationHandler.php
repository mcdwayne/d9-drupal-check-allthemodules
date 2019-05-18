<?php

namespace Drupal\aegir_site_subscriptions_recurly\WebhookNotificationHandlers;

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
