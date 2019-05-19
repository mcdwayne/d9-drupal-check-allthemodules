<?php

namespace Drupal\social_migration\Plugin\QueueWorker;

/**
 * Manual worker to process Social Migration queues.
 *
 * @QueueWorker(
 *   id = "social_migration_manual_queue",
 *   title = @Translation("Social Migration Manual Importer")
 * )
 */
class SocialMigrationManualImporter extends SocialMigrationImporterbase {

  /**
   * Handle success messages.
   *
   * @param string $text
   *   The text to display.
   */
  protected function logSuccessMessage($text) {
    drupal_set_message($text);
  }

  /**
   * Handle failure messages.
   *
   * @param string $text
   *   The text to display.
   */
  protected function logFailureMessage($text) {
    drupal_set_message($text, 'warning');
  }

}
