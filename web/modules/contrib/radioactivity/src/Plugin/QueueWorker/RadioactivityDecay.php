<?php

namespace Drupal\radioactivity\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;

/**
 * Processes radioactivity decay.
 *
 * @QueueWorker(
 *   id = "radioactivity_decay",
 *   title = @Translation("Process radioactivity decay"),
 *   cron = {"time" = 10}
 * )
 */
class RadioactivityDecay extends QueueWorkerBase {

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    /** @var \Drupal\radioactivity\RadioactivityProcessorInterface $processor */
    $processor = \Drupal::service('radioactivity.processor');
    $processor->queueProcessDecay($data['field_config'], $data['entity_ids']);
  }

}
