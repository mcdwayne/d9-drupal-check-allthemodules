<?php

namespace Drupal\recurly_aegir\WebhookNotificationHandlers;

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
