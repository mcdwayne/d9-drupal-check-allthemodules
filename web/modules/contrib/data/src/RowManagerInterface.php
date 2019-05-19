<?php

namespace Drupal\data;

/**
 * Interface RowManagerInterface.
 *
 * @package Drupal\data
 */
interface RowManagerInterface {
  /**
   * @param string $table_name
   *   Table to operate on.
   * @param array $values
   *   Associative array of field values, keyed by column names.
   *
   * @return bool
   *   Result of save operation.
   */
  public function save($table_name, array $values);

}
