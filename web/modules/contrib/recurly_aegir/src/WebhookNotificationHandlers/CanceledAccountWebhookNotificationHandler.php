<?php

namespace Drupal\recurly_aegir\WebhookNotificationHandlers;

/**
 * Processes canceled accounts.
 */
class CanceledAccountWebhookNotificationHandler extends AccountWebhookNotificationHandler {

  /**
   * {@inheritdoc}
   */
  public function handleNotification() {

    // Set the result and return.
    $this->result = TRUE;
    return $this;
  }

}
