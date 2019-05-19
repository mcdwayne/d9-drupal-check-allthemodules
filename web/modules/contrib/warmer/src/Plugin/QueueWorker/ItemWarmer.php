<?php

namespace Drupal\warmer\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\warmer\QueueData;

/**
 * Process the items queued for warming.
 *
 * @QueueWorker(
 *   id = "warmer",
 *   title = @Translation("Cache warmer"),
 *   cron = {"time" = 60}
 * )
 */
class ItemWarmer extends QueueWorkerBase {

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    if (!$data instanceof QueueData) {
      return;
    }
    $data->process();
  }

}
