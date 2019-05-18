<?php

/**
 * @file
 * Contains \Drupal\lcache\BackendFactory.
 */

namespace Drupal\lcache;

use Drupal\Core\Database\Connection;

/**
 * A Factory for an LCache backend.
 */
class BackendFactory {

  protected $integrated;

  /**
   * Constructs the the databse connection for L2.
   */
  protected function getPdoHandle() {
    $db_info = $this->connection->getConnectionOptions();
    $dsn = 'mysql:host=' . $db_info['host'] . ';port=' . $db_info['port'] . ';dbname=' . $db_info['database'];
    $options = array(\PDO::ATTR_TIMEOUT => 2, \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET sql_mode="ANSI_QUOTES,STRICT_ALL_TABLES"');
    $dbh = new \PDO($dsn, $db_info['username'], $db_info['password'], $options);
    $dbh->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    return $dbh;
  }

  /**
   * Constructs the BackendFactory object.
   */
  public function __construct(Connection $connection) {

    $this->connection = $connection;

    // Use the Null L1 cache for the CLI.
    $l1 = new \LCache\NullL1();
    if (php_sapi_name() !== 'cli') {
      $l1 = new \LCache\APCuL1();
    }
    $l2 = new \LCache\DatabaseL2($this->getPdoHandle());
    $this->integrated = new \LCache\Integrated($l1, $l2);
    $this->integrated->synchronize();
  }

  /**
   * Gets an LCache Backend for the specified cache bin.
   *
   * @param string $bin
   *   The cache bin for which the object is created.
   *
   * @return \Drupal\lcache\Backend
   *   The cache backend object for the specified cache bin.
   */
  public function get($bin) {
    return new Backend($bin, $this->integrated);
  }

  /**
   * Gets an LCache Backend for the specified cache bin.
   *
   * @return \LCache\Integrated
   *   The integrated cache backend.
   */
  public function getIntegratedLcache() {
    return $this->integrated;
  }
}
