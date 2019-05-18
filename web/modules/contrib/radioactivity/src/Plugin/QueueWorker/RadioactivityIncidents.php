<?php

namespace Drupal\radioactivity\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;

/**
 * Processes radioactivity emits.
 *
 * @QueueWorker(
 *   id = "radioactivity_incidents",
 *   title = @Translation("Process radioactivity incidents"),
 *   cron = {"time" = 10}
 * )
 */
class RadioactivityIncidents extends QueueWorkerBase {

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    /** @var \Drupal\radioactivity\RadioactivityProcessorInterface $processor */
    $processor = \Drupal::service('radioactivity.processor');
    $processor->queueProcessIncidents($data['entity_type'], $data['incidents']);
  }

}
