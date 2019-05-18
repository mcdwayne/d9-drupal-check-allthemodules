<?php

namespace Drupal\aegir_site_subscriptions_recurly\WebhookNotificationHandlers;

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
