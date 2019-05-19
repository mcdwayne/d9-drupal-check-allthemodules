<?php

namespace Drupal\update_worker\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;

/**
 * Sends a message with sparkpost.
 *
 * @QueueWorker(
 *   id = "update_worker",
 *   title = @Translation("Run arbitrary commands as queue items")
 * )
 */
class UpdateWorker extends QueueWorkerBase {

  const QUEUE_NAME = 'update_worker';

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    $callback = $data['callback'];
    $arguments = $data['arguments'];
    if (is_callable($callback)) {
      call_user_func_array($callback, $arguments);
    }
  }

}
