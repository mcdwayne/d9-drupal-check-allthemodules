<?php

namespace Drupal\sparkpost\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\sparkpost\MessageWrapperInterface;

/**
 * Sends a message with sparkpost.
 *
 * @QueueWorker(
 *   id = "sparkpost_send",
 *   title = @Translation("Send sparkpost message"),
 *   cron = {"time" = 60}
 * )
 */
class SparkpostSend extends QueueWorkerBase {

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    if ($data instanceof MessageWrapperInterface) {
      try {
        $data->sendMessage();
      }
      catch (\Exception $e) {
        // @todo: Handle different exceptions, and possibly react differently.
        // For example could we use SuspendQueueException when we get some sort
        // of http error.
        throw $e;
      }
    }
  }

}
