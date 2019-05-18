<?php

namespace Drupal\aegir_site_subscriptions_recurly\WebhookNotificationHandlers;

/**
 * Processes the closing of invoices.
 */
class ClosedInvoiceWebhookNotificationHandler extends InvoiceWebhookNotificationHandler {

  /**
   * {@inheritdoc}
   */
  public function handleNotification() {

    // Set the result and return.
    $this->result = TRUE;
    return $this;
  }

}
