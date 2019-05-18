<?php

namespace Drupal\prepared_data\Storage;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\SchemaObjectExistsException;

/**
 * Contains reusable methods for SQL storage implementations.
 *
 * Requires the following static variables to be set:
 *   - $table: The name of the SQL table.
 */
trait SqlStorageTrait {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $db;

  /**
   * Returns the schema definition for the SQL table.
   *
   * @return array
   */
  abstract protected function schemaDefinition();

  /**
   * Get the database connection.
   *
   * @return \Drupal\Core\Database\Connection
   *   The database connection.
   */
  public function getDatabase() {
    if (!isset($this->db)) {
      $this->db = \Drupal::service('database');
    }
    return $this->db;
  }

  /**
   * Set the database connection.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   */
  public function setDatabase(Connection $database) {
    $this->db = $database;
  }

  /**
   * Check if the table exists and create it if not.
   *
   * @return bool
   *   TRUE if the table was created, FALSE otherwise.
   *
   * @throws \Drupal\prepared_data\Storage\StorageException
   *   If a database error occurs.
   */
  protected function ensureTableExists() {
    try {
      if (!$this->db->schema()->tableExists(static::$table)) {
        $this->db->schema()->createTable(static::$table, static::schemaDefinition());
        return TRUE;
      }
    }
    // If another process has already created the table, attempting to
    // recreate it will throw an exception. In this case just catch the
    // exception and do nothing.
    catch (SchemaObjectExistsException $e) {
      return TRUE;
    }
    catch (\Exception $e) {
      throw new SqlStorageException($e->getMessage(), NULL, $e);
    }
    return FALSE;
  }

}
