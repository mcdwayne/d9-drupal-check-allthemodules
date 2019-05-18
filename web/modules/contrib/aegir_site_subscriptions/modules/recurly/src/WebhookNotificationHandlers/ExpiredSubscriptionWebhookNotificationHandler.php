<?php

namespace Drupal\aegir_site_subscriptions_recurly\WebhookNotificationHandlers;

use Drupal\node\Entity\Node;

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
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Exception
   */
  public function handleNotification() {
    if ($this->subscription->isActive()) {
      return $this->failAndLogMessage("Subscription %uuid for user %user has not expired; cannot process.");
    }

    $user_id = $this->subscription->getDrupalUserId($this->getAccountCode());

    foreach (array_values($this->subscription->getSiteIds($user_id)) as $site_id) {
      $site = Node::load($site_id);
      $this->siteDeletionService->setSite($site)->performActionAndLogResults();
      $site->save();
    }

    $this->result = TRUE;
    return $this;
  }

}
