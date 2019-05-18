<?php

namespace Drupal\recurly_aegir\WebhookNotificationHandlers;

use Drupal\node\Entity\Node;
use Drupal\recurly_aegir\HostingServiceCalls\SiteDeleteHostingServiceCall;

/**
 * {@inheritdoc}
 *
 * Process expired subscriptions.
 */
class ExpiredSubscriptionWebhookNotificationHandler extends SubscriptionWebhookNotificationHandler {

  /**
   * {@inheritdoc}
   *
   * Delete sites associated with this subscription.
   */
  public function handleNotification() {
    if (!$this->subscription->isExpired()) {
      return $this->failAndLogMessage("Subscription %uuid for user %user has not expired; cannot process.");
    }

    $user_id = $this->subscription->getLocalUserId($this->getAccountCode());

    foreach (array_values($this->subscription->getSiteIds($user_id)) as $site_id) {
      $site = Node::load($site_id);
      $delete_service_call = new SiteDeleteHostingServiceCall($site);
      $delete_service_call->performActionAndLogResults();
      $site->save();
    }

    $this->result = TRUE;
    return $this;
  }

}
