<?php

namespace Drupal\aegir_site_subscriptions_recurly\WebhookNotificationHandlers;

/**
 * Processes updated subscriptions.
 */
class UpdatedSubscriptionWebhookNotificationHandler extends SubscriptionWebhookNotificationHandler {

  /**
   * {@inheritdoc}
   *
   * Process subscription updates.
   *
   * @return $this
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \Exception
   */
  public function handleNotification() {
    if (!$this->subscription->isActive()) {
      return $this->failAndLogMessage("Inactive subscription %uuid for user %user is not longer active. Skipping update processing.");
    }

    if (!$site_entity = $this->subscription->getSiteIfSubscriptionIsActive($this->getAccountCode())) {
      return $this->failAndLogMessage("Could not find site associated with subscription %uuid for user %user. Skipping update processing.");
    }

    $site = $this->siteService->setSite($site_entity);
    $site->setQuotas($this->subscription->getPlanCode(), $this->subscription->getAddons());
    $site->save();

    $this->result = TRUE;
    return $this;
  }

}
