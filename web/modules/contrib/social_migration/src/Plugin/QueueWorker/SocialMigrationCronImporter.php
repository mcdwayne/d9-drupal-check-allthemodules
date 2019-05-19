<?php

namespace Drupal\social_migration\Plugin\QueueWorker;

/**
 * Cron worker to process Social Migration queues.
 *
 * @QueueWorker(
 *   id = "social_migration_cron_queue",
 *   title = @Translation("Social Migration Cron Importer"),
 *   cron = {"time" = 15}
 * )
 */
class SocialMigrationCronImporter extends SocialMigrationImporterBase {

  /**
   * Handle success messages.
   *
   * @param string $text
   *   The text to display.
   */
  protected function logSuccessMessage($text) {
    \Drupal::logger('social_migration')->notice($text);
  }

  /**
   * Handle failure messages.
   *
   * @param string $text
   *   The text to display.
   */
  protected function logFailureMessage($text) {
    \Drupal::logger('social_migration')->error($text);
  }

}
