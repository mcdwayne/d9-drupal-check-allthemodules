<?php

namespace Drupal\transactionalphp;

/**
 * Class DatabaseLazy.
 *
 * Pseudo-namespace wrapper for easy access.
 *
 * @package Drupal\transactionalphp
 *
 * @codeCoverageIgnore
 */
class DatabaseLazy {

  /**
   * Get lazy connection.
   *
   * @param string $target
   *   The database target.
   * @param string $key
   *   (optional) The database key.
   *
   * @return \Drupal\transactionalphp\DatabaseLazyConnection
   *   The lazy database connection.
   */
  static public function getConnection($target = 'default', $key = NULL) {
    return new DatabaseLazyConnection($target, $key);
  }

}
