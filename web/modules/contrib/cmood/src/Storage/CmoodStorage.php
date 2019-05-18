<?php

namespace Drupal\cmood\Storage;

use Drupal\Core\Database\Database;

/**
 * Class CmoodStorage implements storage functions.
 */
class CmoodStorage {

  /**
   * Get cmood content.
   *
   * @param array $header
   *   Header in array form.
   *
   * @return Object
   *   The values.
   */
  public static function getCmoodContent(array $header) {
    $query = db_select('node_field_data', 'n')
      ->addTag('node_access')
      ->fields('nm', ['id', 'nid', 'mood'])
      ->fields('n', ['title']);
    $query->join('node_mood', 'nm', 'nm.nid = n.nid');
    $table_sort = $query->extend('Drupal\Core\Database\Query\TableSortExtender')
      ->orderByHeader($header);
    $pager = $table_sort->extend('Drupal\Core\Database\Query\PagerSelectExtender')
      ->limit(10);
    $result = $pager->execute();

    return $result;
  }

  /**
   * Get cmood words..
   *
   * @param array $header
   *   Header in array form.
   *
   * @return Object
   *   The values.
   */
  public static function getCmoodWords(array $header) {
    $query = db_select('word_with_weight', 'www')->fields('www');
    $table_sort = $query->extend('Drupal\Core\Database\Query\TableSortExtender')
      ->orderByHeader($header);
    $pager = $table_sort->extend('Drupal\Core\Database\Query\PagerSelectExtender')
      ->limit(10);
    $results = $pager->execute();

    return $results;
  }

  /**
   * Get cmood words rank.
   *
   * @param array $header
   *   Header in array form.
   *
   * @return Object
   *   The values.
   */
  public static function getCmoodWordsRank(array $header) {
    $query = db_select('rank_word_with_weight', 'rwww')->fields('rwww');
    $table_sort = $query->extend('Drupal\Core\Database\Query\TableSortExtender')
      ->orderByHeader($header);
    $pager = $table_sort->extend('Drupal\Core\Database\Query\PagerSelectExtender')
      ->limit(10);
    $results = $pager->execute();

    return $results;
  }

  /**
   * Insert records to table.
   *
   * @param string $table
   *   Table Name.
   * @param array $data
   *   Array containing data.
   *
   * @return Object
   *   The values.
   */
  public static function writeCmoodRecords($table, array $data) {
    return Database::getConnection()
      ->insert($table)
      ->fields($data)
      ->execute();
  }

  /**
   * Update records to table.
   *
   * @param string $table
   *   Table Name.
   * @param array $data
   *   Array containing data.
   * @param array $condition
   *   Array containing conditional data.
   *
   * @return Object
   *   The values.
   */
  public static function updateCmoodRecords($table, array $data, array $condition = []) {
    return Database::getConnection()
      ->update($table)
      ->fields($data)
      ->condition($condition['field'], $condition['value'], '=')
      ->execute();
  }

  /**
   * Delete records to table.
   *
   * @param string $table
   *   Table Name.
   * @param array $condition
   *   Array containing conditional data.
   *
   * @return Object
   *   The vlaues.
   */
  public static function deleteCmoodRecords($table, array $condition = []) {
    return Database::getConnection()
      ->delete($table)
      ->condition($condition['field'], $condition['value'], '=')
      ->execute();
  }

  /**
   * Get a single record from table.
   *
   * @param string $table
   *   Table name.
   * @param array $row
   *   Contains column and primary key value.
   *
   * @return Object
   *   The values.
   */
  public static function getCmoodTableDataById($table, array $row) {
    $query = db_select($table, 'table_data')
      ->condition($row['column'], $row['id'])
      ->fields('table_data');
    $results = $query->execute()->fetchAssoc();

    return $results;
  }

  /**
   * Function to return array of weights for add / edit forms.
   *
   * @param array $skip
   *   Array which has skipped values.
   *
   * @return array
   *   This variable contains array of values -20 to 20
   */
  public static function cmoodWeightArray(array $skip = []) {
    $weight = [];
    for ($i = -20; $i <= 20; $i++) {
      if (!in_array($i, $skip)) {
        $weight[$i] = $i;
      }
    }

    return $weight;
  }

}
