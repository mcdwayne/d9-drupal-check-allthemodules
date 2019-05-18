<?php

namespace Drupal\onehub\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\onehub\Batch\OneHubBatch;

/**
 * Updates a feed's items.
 *
 * @QueueWorker(
 *   id = "onehub_queue",
 *   title = @Translation("OneHub Queue"),
 *   cron = {"time" = 3600}
 * )
 */
class OneHubQueue extends QueueWorkerBase {

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    $result = OneHubBatch::processItem($data);
  }
}