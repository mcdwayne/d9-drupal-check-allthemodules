<?php


namespace Drupal\concurrent_users_notification;

/**
 * Class DbStorage.
 */
class DbStorage {

  /**
   * Save an entry in the database.
   *
   * @param array $entry
   *   An array containing all the fields of the database record.
   *
   * @return int
   *   The number of updated rows.
   *
   * @throws \Exception
   *   When the database insert fails.
   *
   * @see db_insert()
   */
  public static function insert($entry) {
    $return_value = NULL;
    try {
      $return_value = \Drupal::database()->insert('concurrent_users_notification')
          ->fields($entry)
          ->execute();
    }
    catch (\Exception $e) {
      \Drupal::logger('concurrent_users_notification')->notice('db_insert failed.');
    }
    return $return_value;
  }

  /**
   * Update an entry in the database.
   *
   * @param array $entry
   *   An array containing all the fields of the item to be updated.
   *
   * @return int
   *   The number of updated rows.
   *
   * @see db_update()
   */
  public static function update($entry) {
    try {
      $count = \Drupal::database()->update('concurrent_users_notification')
          ->fields(array('concurrent_logins_count' => $entry['concurrent_logins_count']))
          ->condition('concurrent_logins_date', $entry['concurrent_logins_date'])
          ->execute();
    }
    catch (\Exception $e) {
      \Drupal::logger('concurrent_users_notification')->notice('db_update failed.');
    }
    return $count;
  }

  /**
   * Delete an entry from the database.
   *
   * @param array $date
   *   An array containing at least the person identifier 'pid' element of the
   *   entry to delete.
   *
   * @see db_delete()
   */
  public static function delete($date) {
    \Drupal::database()->delete('concurrent_users_notification')
        ->condition('concurrent_logins_date', $date)
        ->execute();
  }

  /**
   * Load the data.
   *
   * @param array $date
   *    The person identifier 'pid' element of the
   *   entry to load.
   *
   * @return object $result
   *    Return the result in object format.
   */
  public static function load($date) {
    // Read all fields from the dbtng_example table.
    $result = \Drupal::database()->select('concurrent_users_notification', 'p')
        ->fields('p', array('concurrent_logins_count'))
        ->condition('p.concurrent_logins_date', $date)
        ->execute()
        ->fetchCol();
    // Return the result in object format.
    return $result;
  }

  /**
   * Load the session count.
   *
   * @return object $count
   *    Return the result in object format.
   */
  public static function loadSessionCount() {
    // Since logged in users never time out, it may be useful to exclude logged
    // in users who haven't accessed the site in a while
    // (i.e. maybe an hour of inactivity):
    $timestamp = time() - 3600;
    $count = \Drupal::database()->select('sessions')
        ->condition('timestamp', $timestamp, '>')
        ->condition('uid', 0, '>')
        ->countQuery()
        ->execute()
        ->fetchField();
    // Return the result in object format.
    return $count;
  }

}
