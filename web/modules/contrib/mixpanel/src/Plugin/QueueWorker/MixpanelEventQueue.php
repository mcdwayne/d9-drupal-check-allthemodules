<?php

/**
 * @file
 * Contains \Drupal\mixpanel\Plugin\QueueWorker\MixpanelEventQueue.
 */

namespace Drupal\mixpanel\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;

/**
 * @QueueWorker(
 *   id = "mixpanel_track",
 *   title = @Translation("Mixpanel event queue"),
 *   cron = {"time" = 60}
 * )
 */
class MixpanelEventQueue extends QueueWorkerBase {

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    // Actually send the request to the Mixpanel API.
    $success = _mixpanel_track($data['event'], $data['properties']);

    // If it was unsuccessful, we re-queue the item.
    if (!$success) {
      $queue = DrupalQueue::get('mixpanel_track');
      $queue->createItem($data);
    }
  }

}
