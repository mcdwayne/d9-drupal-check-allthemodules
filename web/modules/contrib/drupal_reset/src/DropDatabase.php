<?php

namespace Drupal\drupal_reset;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Database;

/**
 * Class DropDatabase.
 *
 * @package Drupal\drupal_reset
 */
class DropDatabase implements DropDatabaseInterface {

  protected $connection;

  /**
   * Constructor.
   */
  public function __construct(Connection $connection) {
    $this->connection = $connection;
  }

  /**
   * Delete all database tables.
   */
  public function dropdatabase() {
    $prefix = Database::getConnectionInfo('default')['default']['prefix']['default'];
    $schema = $this->connection->schema();
    $tables = $schema->findTables($prefix . '%');
    foreach ($tables as $table) {
      $schema->dropTable($table);
    }
  }

  /**
   * Check if the installation uses a single-database and a simple prefix.
   *
   * @return bool
   *   TRUE is the installation uses a single-database and a simple prefix, FALSE
   *   otherwise.
   */
  public function validateIsSupported() {
    $database = Database::getConnectionInfo('default');
    return ($database['default'] && (count($database) === 1) &&
      isset($database['default']['prefix']['default']) &&
      is_string($database['default']['prefix']['default'])
    );
  }

}
