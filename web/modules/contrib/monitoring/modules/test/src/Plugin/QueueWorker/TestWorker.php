<?php

namespace Drupal\monitoring_test\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;

/**
 * Test worker.
 *
 * @QueueWorker(
 *   id = "monitoring_test",
 *   title = @Translation("Test Worker"),
 *   cron = {"time" = 60}
 * )
 */
class TestWorker extends QueueWorkerBase {

  /**
   * {@inheritdoc}
   */
  public function processItem($data) { }

}
