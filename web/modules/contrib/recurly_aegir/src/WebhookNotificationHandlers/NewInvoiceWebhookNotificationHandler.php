<?php

namespace Drupal\recurly_aegir\WebhookNotificationHandlers;

/**
 * Processes new invoices.
 */
class NewInvoiceWebhookNotificationHandler extends InvoiceWebhookNotificationHandler {

  /**
   * {@inheritdoc}
   */
  public function handleNotification() {

    // Set the result and return.
    $this->result = TRUE;
    return $this;
  }

}
