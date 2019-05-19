<?php

/**
 * @file
 * Contains \Drupal\smartling\SubmissionStorage.
 */

namespace Drupal\smartling;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;

/**
 * Defines a handler class for smartling submissions.
 */
class SubmissionStorage extends SqlContentEntityStorage implements SubmissionStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function getSubmissionsIdsToCheckStatus() {
    $ids = \Drupal::entityQuery('smartling_submission')
      ->condition('status', SmartlingSubmissionInterface::TRANSLATING)
      ->condition('changed', REQUEST_TIME - SMARTLING_CRON_RUN_INTERVAL, '<')
      ->execute();
    return $ids;
  }

  public function loadByConditions($conditions = []) {
    $qr = \Drupal::entityQuery('smartling_submission');
    foreach ($conditions as $key => $val) {
      $qr->condition($key, $val);
    }
    $ids = $qr->execute();

    return $ids;
  }

}
