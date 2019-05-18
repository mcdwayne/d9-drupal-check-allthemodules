<?php

/**
 * @file
 * Contains \Drupal\page_message\PagemessageStorage
 */

namespace Drupal\page_message;

class PagemessageStorage {

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
      $return_value = db_insert('page_message')
          ->fields($entry)
          ->execute();
    }
    catch (\Exception $e) {
      drupal_set_message(t('db_insert failed. Message = %message, query= %query', array(
            '%message' => $e->getMessage(),
            '%query' => $e->query_string,
          )), 'error');
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
      // db_update()...->execute() returns the number of rows updated.
      $count = db_update('page_message')
          ->fields($entry)
          ->condition('pmid', $entry['pmid'])
          ->execute();
    }
    catch (\Exception $e) {
      drupal_set_message(t('db_update failed. Message = %message, query= %query', array(
            '%message' => $e->getMessage(),
            '%query' => $e->query_string,
          )), 'error');
    }
    return $count;
  }

  /**
   * Delete an entry from the database.
   *
   * @param array $entry
   *   An array containing at least the page/message identifier 'pmid' element of the
   *   entry to delete.
   *
   * @see db_delete()
   */
  public static function delete($pmid) {
    db_delete('page_message')
        ->condition('pmid', $pmid)
        ->execute();
    return TRUE;
  }

  /**
   * Read page/message entries from the database.
   *
   * @return object
   *   An object containing the loaded entries if found.
   *
   * @see db_query()
   */
  public static function load() {
    // Read all fields from the page_message table.
    // SELECT * FROM {page_message} WHERE uid = 0 AND name = 'John'

    $messages = db_query(
      "SELECT pmid, page, message, from_unixtime(created) as created, from_unixtime(updated) as updated FROM {page_message} ORDER BY page, created"
    )->fetchAll();
    // Return the result as an array of objects.
    return $messages;
  }

  /**
   * Search for page/message entries containing a particular path. There could be zero, one, or more.
   *
   * @return object
   *   An object containing the loaded entries if found.
   *
   * @see db_query()
   */
  public static function search($path) {
    // Read all fields from the page_message table matching $path.

    $messages = db_query(
      "SELECT * FROM {page_message} WHERE page = :path ORDER BY created", array(':path' => $path)
    )->fetchAll();
    // Return the result as an array of objects.
    return $messages;
  }


}
