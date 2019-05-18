<?php

namespace Drupal\recurly_aegir\WebhookNotificationHandlers;

use Drupal\recurly_aegir\Wrappers\SiteWrapper;

/**
 * Processes updated subscriptions.
 */
class UpdatedSubscriptionWebhookNotificationHandler extends SubscriptionWebhookNotificationHandler {

  /**
   * {@inheritdoc}
   *
   * Process subscription updates.
   */
  public function handleNotification() {
    if (!$this->subscription->isActive()) {
      return $this->failAndLogMessage("Inactive subscription %uuid for user %user is not longer active. Skipping update processing.");
    }

    if (!$site = $this->subscription->getSiteIfSubscriptionIsActive($this->getAccountCode())) {
      return $this->failAndLogMessage("Could not find site associated with subscription %uuid for user %user. Skipping update processing.");
    }

    $site_wrapper = new SiteWrapper($site);
    $site_wrapper->setQuotas($this->subscription->getPlanCode(), $this->subscription->getAddons());
    $site_wrapper->save();

    $this->result = TRUE;
    return $this;
  }

}
