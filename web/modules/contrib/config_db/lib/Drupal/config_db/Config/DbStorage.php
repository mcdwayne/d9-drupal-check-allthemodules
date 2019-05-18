<?php

/**
 * @file
 * Definition of Drupal\config_db\Config\DbStorage.
 */

namespace Drupal\config_db\Config;

use Drupal\Core\Config\DatabaseStorage;
use Drupal\Core\Database\Connection;

class DbStorage extends DatabaseStorage {

  /**
   * Constructs the DatabaseBackendFactory object.
   *
   * @param \Drupal\Core\Database\Connection $connection
   */
  function __construct(Connection $connection) {
    parent::__construct($connection, 'config_db_config', array());
  }
}

