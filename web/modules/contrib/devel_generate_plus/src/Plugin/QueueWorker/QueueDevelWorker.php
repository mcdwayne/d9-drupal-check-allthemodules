<?php

namespace Drupal\concurrent_queue\Plugin\QueueWorker;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;

/**
 *
 * @QueueWorker(
 *  id = "queue_devel_generate",
 *  title = "Queue Devel Worker Dummy",
 *  deriver = "Drupal\devel_generate_plus\Plugin\Derivative\QueueDevelWorker",
 *  cron = {"time" = 10}
 * )
 */
class QueueDevelWorker extends QueueWorkerBase  {
  /**
   * Does nothing.
   */
  public function processItem($data) {

  }
}
