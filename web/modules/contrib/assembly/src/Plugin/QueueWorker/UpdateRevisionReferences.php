<?php

namespace Drupal\assembly\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;

/**
 * A Node Publisher that publishes nodes on CRON run.
 *
 * @QueueWorker(
 *   id = "assembly_update_revision_references",
 *   title = @Translation("Update Assembly Revision References"),
 *   cron = {"time" = 30}
 * )
 */
class UpdateRevisionReferences extends QueueWorkerBase {
  /**
   * {@inheritdoc}
   *
   * Calls update existing on the passed in assembly.
   */
  public function processItem($data) {
    $vid = \Drupal::database()->select('assembly', 'a')
      ->fields('a', ['vid'])
      ->condition('id', $data)
      ->execute()
      ->fetchField();

    _assembly_update_existing($data, $vid);
  }
}
