<?php

namespace Drupal\webfactory_master\SiteDeploy\Sql;

/**
 * Define behavior of SQL driver.
 *
 * @package Drupal\webfactory_master\SiteDeploy\Sql
 */
interface SqlDriverInterface {

  /**
   * Open connection to MySQL server.
   *
   * @param string $host
   *   Sql server hostname.
   * @param int $port
   *   Sql server port.
   * @param string $login
   *   Sql user login.
   * @param string $pwd
   *   Sql user password.
   *
   * @return \PDO
   *   PDO Connection.
   */
  public function open($host, $port, $login, $pwd);

  /**
   * Check if given database already exists.
   *
   * @param string $db_name
   *   Database name to check.
   *
   * @return bool
   *   True if database exists, false otherwise.
   */
  public function dbExists($db_name);

  /**
   * Create given database.
   *
   * @param string $db_name
   *   Database name.
   */
  public function createDb($db_name);

  /**
   * Drop given database.
   *
   * @param string $db_name
   *   Database name.
   */
  public function dropDb($db_name);

}
