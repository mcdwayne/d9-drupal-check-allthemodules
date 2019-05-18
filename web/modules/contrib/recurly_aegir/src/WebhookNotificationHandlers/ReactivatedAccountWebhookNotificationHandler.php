<?php

namespace Drupal\recurly_aegir\WebhookNotificationHandlers;

/**
 * Processes reactivated accounts.
 */
class ReactivatedAccountWebhookNotificationHandler extends AccountWebhookNotificationHandler {

  /**
   * {@inheritdoc}
   */
  public function handleNotification() {

    // Set the result and return.
    $this->result = TRUE;
    return $this;
  }

}
