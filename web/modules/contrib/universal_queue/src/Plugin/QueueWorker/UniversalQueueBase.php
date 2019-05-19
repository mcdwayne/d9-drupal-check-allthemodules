<?php

/**
 * @file
 * Contains Drupal\universal_queue\Plugin\QueueWorker\UniversalQueueBase.php
 */

namespace Drupal\universal_queue\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;


/**
 * Provides base functionality for the Universal Queue Workers.
 */
abstract class UniversalQueueBase extends QueueWorkerBase {

  /**
   * {@inheritdoc}
   */
  public function processItem($item) {
    // Include file if it exists.
    if (!empty($item['file']) && file_exists(DRUPAL_ROOT . '/' . $item['file'])) {
      include_once DRUPAL_ROOT . '/' . $item['file'];
    }
    if (!isset($item['params'])) {
      $item['params'] = array();
    }
    call_user_func_array($item['callback'], $item['params']);
  }
}
