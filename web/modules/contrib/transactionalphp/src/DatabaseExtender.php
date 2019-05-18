<?php

namespace Drupal\transactionalphp;

use Drupal\Core\Database\Database;

/**
 * Class DatabaseExtender.
 *
 * Provide extra functionality for database connections.
 *
 * @package Drupal\transactionalphp
 *
 * @codeCoverageIgnore
 */
abstract class DatabaseExtender extends Database {

  /**
   * Get active key.
   *
   * @return string
   *   Current active database key.
   */
  static public function getActiveKey() {
    return self::$activeKey;
  }

  /**
   * Check if key/target is connected.
   *
   * @param string $target
   *   The database target.
   * @param string $key
   *   (optional) The database key.
   *
   * @return bool
   *   TRUE if key/target is currently connected.
   */
  static public function isConnected($target = 'default', $key = NULL) {
    $key = isset($key) ? $key : self::$activeKey;
    return isset(self::$connections[$key][$target]);
  }

}
