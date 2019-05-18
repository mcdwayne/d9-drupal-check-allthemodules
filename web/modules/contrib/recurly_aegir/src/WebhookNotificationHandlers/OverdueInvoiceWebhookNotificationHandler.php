<?php

namespace Drupal\recurly_aegir\WebhookNotificationHandlers;

/**
 * Processes overdue invoices.
 */
class OverdueInvoiceWebhookNotificationHandler extends InvoiceWebhookNotificationHandler {

  /**
   * {@inheritdoc}
   */
  public function handleNotification() {

    // Set the result and return.
    $this->result = TRUE;
    return $this;
  }

}
