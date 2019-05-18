<?php

namespace Drupal\recurly_aegir\WebhookNotificationHandlers;

/**
 * Processes new accounts.
 */
class NewAccountWebhookNotificationHandler extends AccountWebhookNotificationHandler {

  /**
   * {@inheritdoc}
   */
  public function handleNotification() {

    // Set the result and return.
    $this->result = TRUE;
    return $this;
  }

}
