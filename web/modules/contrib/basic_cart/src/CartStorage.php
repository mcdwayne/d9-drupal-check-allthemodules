<?php

namespace Drupal\basic_cart;

/**
 * Class CartStorage.
 */
class CartStorage {

  const TABLE = 'basic_cart_cart';

  /**
   * Insert a cart entry in the database.
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
  public static function insert(array $entry) {
    $return_value = NULL;
    try {
      $return_value = db_insert(self::TABLE)
        ->fields($entry)
        ->execute();
    }
    catch (\Exception $e) {
      drupal_set_message(t('db_insert failed. Message = %message, query= %query', array(
        '%message' => $e->getMessage(),
        '%query' => $e->query_string,
      )
      ), 'error');
    }
    return $return_value;
  }

  /**
   * Update a cart entry in the database.
   *
   * @param array $entry
   *   An array containing all the fields of the item to be updated.
   *
   * @return int
   *   The number of updated rows.
   *
   * @see db_update()
   */
  public static function update(array $entry) {
    try {
      // db_update()...->execute() returns the number of rows updated.
      $count = db_update(self::TABLE)
        ->fields($entry)
        ->condition('uid', $entry['uid'])
        ->condition('id', $entry['id'])
        ->condition('entitytype', $entry['entitytype'])
        ->execute();
    }
    catch (\Exception $e) {
      drupal_set_message(t('db_update failed. Message = %message, query= %query', array(
        '%message' => $e->getMessage(),
        '%query' => $e->query_string,
      )
      ), 'error');
    }
    return $count;
  }

  /**
   * Delete a cart entry from the database.
   *
   * @param array $entry
   *   An array containing at least the person identifier 'pid' element of the
   *   entry to delete.
   *
   * @see db_delete()
   */
  public static function delete(array $entry) {
    $delete = db_delete(self::TABLE);
    if ($entry['uid']) {
      $delete->condition('uid', $entry['uid']);
    }
    if ($entry['id']) {
      $delete->condition('id', $entry['id']);
      $delete->condition('entitytype', $entry['entitytype'] ? $entry['entitytype'] : 'node');
    }
    $delete->execute();
  }

  /**
   * Read from the Cart data from database.
   *
   * @param array $entry
   *   An array containing all the fields used to search the entries in the
   *   table.
   *
   * @return object
   *   An object containing the loaded entries if found.
   *
   * @see db_select()
   * @see db_query()
   * @see http://drupal.org/node/310072
   * @see http://drupal.org/node/310075
   */
  public static function load(array $entry = array()) {
    // Read all fields from the dbtng_example table.
    $select = db_select(self::TABLE, 'cart');
    $select->fields('cart');

    // Add each field and value as a condition to this query.
    foreach ($entry as $field => $value) {
      $select->condition($field, $value);
    }
    // Return the result in object format.
    return $select->execute()->fetchAll();
  }

}
