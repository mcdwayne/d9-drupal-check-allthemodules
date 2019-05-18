<?php

/**
 * @file
 * Contains \Drupal\govdelivery\Plugins\QueueWorker\GovDeliveryMailWorker.
 */

namespace Drupal\govdelivery\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;

/**
 * Processes Tasks for Learning.
 *
 * @QueueWorker(
 *   id = "govdelivery_tms_mailsystem",
 *   title = @Translation("Mail Processor"),
 *   cron = {"time" = 60}
 * )
 */
class GovDeliveryMailWorker extends QueueWorkerBase {
  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    // attempt to send the message
    if (!govdelivery_send_message($data)) {
      // send message failed
      throw new \Drupal\Core\Queue\SuspendQueueException();
    }
  }
}

