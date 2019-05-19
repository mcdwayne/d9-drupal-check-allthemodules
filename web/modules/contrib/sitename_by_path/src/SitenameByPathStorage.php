<?php

namespace Drupal\sitename_by_path;

/**
 * Class SitenameByPathStorage.
 */
class SitenameByPathStorage {

  /**
   * Save an entry in the database.
   */
  public static function insert($entry) {
    $return_value = NULL;
    try {
      $return_value = db_insert('sitename_by_path')
        ->fields($entry)
        ->execute();
    }
    catch (\Exception $e) {
      drupal_set_message(t('db_insert failed. Message = %message, query= %query', [
        '%message' => $e->getMessage(),
        '%query' => $e->query_string,
      ]
      ), 'error');
    }
    return $return_value;
  }

  /**
   * Update an entry in the database.
   */
  public static function update($entry) {
    $update = db_update('sitename_by_path')
      ->fields($entry)
      ->condition('id', $entry['id']);
    return $update->execute();
  }

  /**
   * Delete an entry from the database.
   */
  public static function delete($id = NULL) {
    $delete = db_delete('sitename_by_path')
      ->condition('id', $id);
    return $delete->execute();
  }

  /**
   * Get all entries in Sitename_By_Path database table.
   */
  public static function load($entry = [], $sbp_id = NULL) {
    $query = db_select('sitename_by_path', 's')
      ->fields('s', ['id', 'path', 'sitename', 'frontpage']);
    return $query->execute()->fetchAll();
  }

}
