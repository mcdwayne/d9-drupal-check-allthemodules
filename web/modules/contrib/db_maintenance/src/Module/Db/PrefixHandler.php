<?php

/**
 * @file
 * PrefixHandler class.
 */

namespace Drupal\db_maintenance\Module\Db;

use Drupal\Core\Database\Database;

/**
 * PrefixHandler class.
 */
class PrefixHandler {

  /**
   * Returns table prefix.
   */
  public static function getPrefix($table) {
    $px = Database::getConnection()->tablePrefix($table);
    return $px;
  }

  /**
   * Cleans table prefix.
   */
  public static function clearPrefix($table) {
    $px = self::getPrefix($table);
    if (strlen($px) > 0) {
      $table_clear = str_replace($px, '', $table);
      return $table_clear;
    }
    return $table;
  }

}
