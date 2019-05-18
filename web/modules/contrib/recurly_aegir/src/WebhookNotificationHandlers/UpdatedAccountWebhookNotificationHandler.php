<?php

namespace Drupal\recurly_aegir\WebhookNotificationHandlers;

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
