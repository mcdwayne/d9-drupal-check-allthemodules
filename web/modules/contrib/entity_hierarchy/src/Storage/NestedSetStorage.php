<?php

namespace Drupal\entity_hierarchy\Storage;

use Doctrine\DBAL\Connection;
use PNX\NestedSet\Storage\DbalNestedSet;
use PNX\NestedSet\Storage\DbalNestedSetSchema;
use Psr\Log\LoggerInterface;

/**
 * Wraps the library nested set implementation with JIT table creation.
 */
class NestedSetStorage {

  /**
   * Schema for storage.
   *
   * @var \PNX\NestedSet\Storage\DbalNestedSetSchema
   */
  protected $schema;

  /**
   * Proxy for storage.
   *
   * @var \PNX\NestedSet\Storage\DbalNestedSet
   */
  protected $proxy;

  /**
   * Logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Table name.
   *
   * @var string
   */
  protected $tableName;

  /**
   * Constructs a new NestedSetStorage object.
   *
   * @param \Doctrine\DBAL\Connection $connection
   *   Connection.
   * @param string $table_name
   *   Table name.
   * @param \Psr\Log\LoggerInterface $logger
   *   Logger.
   */
  public function __construct(Connection $connection, $table_name, LoggerInterface $logger) {
    $this->schema = new DbalNestedSetSchema($connection, $table_name);
    $this->proxy = new DbalNestedSet($connection, $table_name);
    $this->logger = $logger;
    $this->tableName = $table_name;
  }

  /**
   * {@inheritdoc}
   */
  public function __call($name, $arguments) {
    $try_again = FALSE;
    try {
      return $this->doCall($name, $arguments);
    }
    catch (\InvalidArgumentException $e) {
      $this->logger->emergency(sprintf('The nested set table %s is corrupt and needs to be rebuilt. Use drush entity-hierarchy-tree-rebuild command.', $this->tableName));
      // Library can throw InvalidArgumentException. Let's self heal.
      if ($name === 'getNode') {
        return FALSE;
      }
      if ($name === 'findParent') {
        return FALSE;
      }
      if (strpos($name, 'find') === 0) {
        return [];
      }
    }
    catch (\Exception $e) {
      // If there was an exception, try to create the table.
      if (!$try_again = $this->ensureTableExists()) {
        // If the exception happened for other reason than the missing table,
        // propagate the exception.
        throw $e;
      }
    }
    // Now that the table has been created, try again if necessary.
    if ($try_again) {
      return $this->doCall($name, $arguments);
    }
    throw new \LogicException('Unexpected exception occurred.');
  }

  /**
   * Calls proxied class.
   *
   * @param string $name
   *   Method name.
   * @param array $arguments
   *   Method arguments.
   *
   * @return mixed
   *   Result of proxied call.
   */
  protected function doCall($name, array $arguments) {
    return call_user_func_array([$this->proxy, $name], $arguments);
  }

  /**
   * Creates the table if required.
   */
  protected function ensureTableExists() {
    try {
      $this->schema->create();
      return TRUE;
    }
    catch (\Exception $e) {
      return FALSE;
    }
  }

}
