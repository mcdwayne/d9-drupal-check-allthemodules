<?php

namespace Drupal\recurly_aegir\WebhookNotificationHandlers;

/**
 * Processes new subscriptions.
 */
class NewSubscriptionWebhookNotificationHandler extends SubscriptionWebhookNotificationHandler {

  /**
   * {@inheritdoc}
   *
   * Process new subscriptions.
   */
  public function handleNotification() {
    if (!$this->subscription->isActive()) {
      return $this->failAndLogMessage("Inactive subscription %uuid created by user %user. Skipping site creation.");
    }

    if ($this->subscription->getSite($this->getAccountCode())) {
      return $this->failAndLogMessage("A site has already been created for user %user with subscription %uuid. Skipping.");
    }

    $user_id = $this->subscription->getLocalUserId($this->getAccountCode());
    $this->subscription->createSite($user_id);

    $this->result = TRUE;
    return $this;
  }

}
