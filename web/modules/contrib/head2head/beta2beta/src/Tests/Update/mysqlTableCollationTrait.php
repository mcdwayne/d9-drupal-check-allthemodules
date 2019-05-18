<?php

/**
 * @file
 * Contains \Drupal\beta2beta\Tests\Update\mysqlTableCollationTrait.
 */

namespace Drupal\beta2beta\Tests\Update;

use Drupal\Core\Database\Database;
use Drupal\Component\Utility\SafeMarkup;

/**
 * Trait for examining MySQL table collation.
 */
trait mysqlTableCollationTrait {

  /**
   * Determine the collation type for a given table and field.
   *
   * Note, this is only compatible with MySQL.
   *
   * @param string $table
   *   The table name.
   * @param string $field
   *   The field name.
   *
   * @return string|bool
   *   The MySQL field collation. Returns FALSE if the field isn't found.
   */
  protected function getColumnCollation($table, $field) {
    $query = Database::getConnection()->query("SHOW FULL COLUMNS FROM {" . $table . "}");
    while ($row = $query->fetchAssoc()) {
      if ($row['Field'] === $field) {
        return $row['Collation'];
      }
    }
    $this->fail(SafeMarkup::format('No collation found for %table.%field', ['%table' => $table, '%field' => $field]));
  }

  /**
   * Determine the table collation for a given table.
   *
   * @param string $table
   *   The table to check.
   *
   * @return string
   *   The table collation.
   */
  protected function getTableCollation($table) {
    $prefixed_table = Database::getConnection()->tablePrefix() . $table;
    $query = Database::getConnection()->query("SHOW TABLE STATUS LIKE '$prefixed_table'");
    while ($row = $query->fetchAssoc()) {
      return $row['Collation'];
    }
    $this->fail(SafeMarkup::format('No collation found for %table', ['%table' => $table]));
  }

}
