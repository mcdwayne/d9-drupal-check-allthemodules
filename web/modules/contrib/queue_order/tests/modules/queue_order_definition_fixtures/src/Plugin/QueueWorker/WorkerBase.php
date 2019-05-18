<?php

namespace Drupal\queue_order_definition_fixtures\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;

/**
 * Class WorkerBase.
 *
 * Base Queue Worker class for test Queue Worker classes.
 *
 * @package Drupal\queue_order_definition_fixtures\Plugin\QueueWorker
 */
abstract class WorkerBase extends QueueWorkerBase {

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {}

}
