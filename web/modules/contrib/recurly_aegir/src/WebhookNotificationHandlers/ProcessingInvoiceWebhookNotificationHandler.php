<?php

namespace Drupal\recurly_aegir\WebhookNotificationHandlers;

/**
 * Processes invoices that are being processed.
 */
class ProcessingInvoiceWebhookNotificationHandler extends InvoiceWebhookNotificationHandler {

  /**
   * {@inheritdoc}
   */
  public function handleNotification() {

    // Set the result and return.
    $this->result = TRUE;
    return $this;
  }

}
