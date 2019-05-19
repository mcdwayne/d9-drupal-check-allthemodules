<?php

namespace Drupal\simple_access_log;

/**
 * Provides the default database storage backend for statistics.
 */
class SimpleAccessLogDatabaseStorage implements SimpleAccessLogStorageInterface {

  /**
   * {@inheritdoc}
   */
  public static function logAccess($values) {
    $db = \Drupal::database();
    if ($db) {
      $db->insert('simple_access_log')->fields($values)->execute();
      return TRUE;
    }else {
      \Drupal::logger('simple_access_log')->error('Could not write to database.');
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function purgeOld($timestamp) {
    // Get a database connection.
    $db = \Drupal::database();
    // Delete values older than the oldest timestamp.
    $num_deleted = $db->delete('simple_access_log')
      ->condition('timestamp', $timestamp, '<')
      ->execute();
    // Return the number of records deleted. I don't think we're
    // actually doing anything with this value.
    return $num_deleted;
  }

}
