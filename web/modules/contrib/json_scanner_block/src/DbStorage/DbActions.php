<?php

namespace Drupal\json_scanner_block\DbStorage;

/**
 * Class DbActions.
 */
class DbActions {

    /**
     * Save an entry in the database.
     *
     * The underlying function in this class is db_insert().
     *
     * Exception handling also followed so we written baseclass here. It could be simplified
     * without the try/catch blocks, but since an insert will throw an exception
     * and terminate your application if the exception is not handled, it is best
     * to employ try/catch.
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
    public static function insert(array $entry, string $dbname) {
        $return_value = NULL;
        try {
            $return_value = db_insert($dbname)
                    ->fields($entry)
                    ->execute();
        } catch (\Exception $e) {
            \Drupal::messenger()->addMessage(t('db_insert failed. Message = %message, query= %query', [
                '%message' => $e->getMessage(),
                '%query' => $e->query_string,
                            ]
                    ), 'error');
        }
        return $return_value;
    }

    /**
     * Read from the database using a filter array.
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
    public static function load(string $table_name, array $entry = []) {
        // Read all fields from the dbtng_example table.
        $select = db_select($table_name, 'tbl');
        $select->fields('tbl');

        // Add each field and value as a condition to this query.
        foreach ($entry as $field => $value) {
            $select->condition($field, $value);
        }
        // Return the result in object format.
        return $select->execute()->fetchAll();
    }

    /**
     * Return data from a single field with matching condition field.
     * 
     * @param type $findData
     * @param type $match_condition
     * @param type $field_name
     * @param type $table_name
     * @return type
     */
    public static function getSingleDatas($findData, $match_condition, $field_name, $table_name, $isLike = '=') {
        $isLike = strtolower($isLike);
        if($isLike == 'like'){
           $findData = '%'. $findData . '%';
        }
        $select = \Drupal::database()->select($table_name, 'json_scanner_block');
        $select->addField('json_scanner_block', $field_name);
        $select->condition('json_scanner_block.'.$match_condition, $findData, $isLike);
        $entries = $select->execute()->fetchAll();
        return $entries;
    }
    
      /**
   * Delete an entry from the database.
   *
   * @param array $entry
   *   An array containing at least the identifier 'id' element of the
   *   entry to delete.
   *
   * @see db_delete()
   */
  public static function delete(string $table_name, string $match_base, string $column_base, array $entry = []) {
    db_delete($table_name)
      ->condition($match_base, $column_base)
      ->execute();
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
  public static function update(array $entry) {
    try {
      // db_update()...->execute() returns the number of rows updated.
      $count = db_update('json_scanner_block')
        ->fields($entry)
        ->condition('id', $entry['id'])
        ->execute();
    }
    catch (\Exception $e) {
      \Drupal::messenger()->addMessage(t('db_update failed. Message = %message, query= %query', [
        '%message' => $e->getMessage(),
        '%query' => $e->query_string,
      ]
      ), 'error');
    }
    return $count;
  }

}
