<?php

namespace Drupal\webfactory_master\SiteDeploy\Sql\mysql;

use Drupal\webfactory_master\SiteDeploy\Sql\SqlDriverInterface;

/**
 * Implements MySQL Driver.
 *
 * @package Drupal\webfactory_master\SiteDeploy\Sql\mysql
 */
class Driver implements SqlDriverInterface {

  /**
   * Current PDO connection.
   *
   * @var \PDO
   */
  protected $connection;

  /**
   * Open connection to MySQL server.
   *
   * @param string $host
   *   Mysql server hostname.
   * @param int $port
   *   Mysql server port.
   * @param string $login
   *   Mysql user login.
   * @param string $pwd
   *   Mysql user password.
   *
   * @return \PDO
   *   PDO Connection.
   */
  public function open($host, $port, $login, $pwd) {
    $this->connection = new \PDO(
      'mysql:host=' . $host . ';port=' . $port . ';dbname=INFORMATION_SCHEMA',
      $login,
      $pwd
    );

    return $this->connection;
  }

  /**
   * Check if given database already exists.
   *
   * @param string $db_name
   *   Database name to check.
   *
   * @return bool
   *   True if database exists, false otherwise.
   */
  public function dbExists($db_name) {
    $query = 'SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = :db';
    $stmt = $this->connection->prepare($query);
    $stmt->execute([':db' => $db_name]);
    return $stmt->rowCount() > 0;
  }

  /**
   * Create given database.
   *
   * @param string $db_name
   *   Database name.
   */
  public function createDb($db_name) {
    $query = 'CREATE DATABASE ' . $this->connection->quote($db_name);
    $this->connection->exec($query);
  }

  /**
   * Drop given database.
   *
   * @param string $db_name
   *   Database name.
   */
  public function dropDb($db_name) {
    $query = 'DROP DATABASE ' . $db_name;
    $this->connection->exec($query);
  }

}
