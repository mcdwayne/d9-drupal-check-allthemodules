<?php

namespace Drupal\campaignmonitor_local\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;

/**
 * Provides base functionality for the Campaignmonitor Queue Workers.
 *
 * @QueueWorker(
 *   id = "campaignmonitor_local_subscriptions",
 *   title = @Translation("Campaign Monitor local subscriptions"),
 * )
 */
class SubscriptionsQueue extends QueueWorkerBase {

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    $uid = $data['uid'];
    $mail = $data['mail'];
    // Get the CM subscription.
    $subs = campaignmonitor_user_get_user_subscriptions($mail);
    // Merge the data record.
    campaignmonitor_local_insert_user_data($uid, $subs);
  }

}
