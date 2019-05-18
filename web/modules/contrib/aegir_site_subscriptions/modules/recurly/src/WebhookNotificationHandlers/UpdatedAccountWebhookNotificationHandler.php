<?php

namespace Drupal\aegir_site_subscriptions_recurly\WebhookNotificationHandlers;

/**
 * Processes updated accounts.
 */
class UpdatedAccountWebhookNotificationHandler extends AccountWebhookNotificationHandler {

  /**
   * {@inheritdoc}
   */
  public function handleNotification() {

    // Set the result and return.
    $this->result = TRUE;
    return $this;
  }

}
