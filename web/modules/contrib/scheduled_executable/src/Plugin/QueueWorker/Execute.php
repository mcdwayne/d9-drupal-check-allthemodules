<?php

namespace Drupal\scheduled_executable\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;

/**
 * @QueueWorker(
 *   id = "scheduled_executable_execute",
 *   title = @Translation("Executes scheduled executables"),
 *   cron = {"time" = 15}
 * )
 */
class Execute extends QueueWorkerBase {

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    // TODO: inject this.
    $scheduled_executable_storage = \Drupal::entityTypeManager()->getStorage('scheduled_executable');

    $scheduled_executable = $scheduled_executable_storage->load($data);

    $plugin = $scheduled_executable->getExecutablePluginInstance();
    $target_entity = $scheduled_executable->getTargetEntity();

    // TODO: catch exception?
    $plugin->execute($target_entity);

    $scheduled_executable->delete();
  }

}
