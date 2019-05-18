<?php

namespace Drupal\dbal;

use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\DriverManager;
use Drupal\Core\Database\Database;

/**
 * Provides a connection factory for connection to databases via doctrine/dbal.
 */
class ConnectionFactory {

  /**
   * Connection info.
   *
   * @var array
   */
  protected $info;

  /**
   * Connection cache.
   *
   * @var \Doctrine\DBAL\Connection[]
   */
  protected $cache;

  /**
   * Constructs a new ConnectionFactory object.
   */
  public function __construct() {
    $this->info = Database::getAllConnectionInfo();
  }

  /**
   * {@inheritdoc}
   */
  public function __sleep() {
    // We don't serialize the connection cache.
    return ['info'];
  }

  /**
   * Gets a DBAL connection to the given target.
   *
   * @param string $target
   *   Database connection as named in global $databases parameter.
   *
   * @return \Doctrine\DBAL\Connection
   *   Requested connection.
   */
  public function get($target = 'default') {
    if (!isset($this->cache[$target])) {
      if (!isset($this->info[$target])) {
        // Fallback to default connection.
        $target = 'default';
      }
      $info = $this->info[$target]['default'];
      $options = [
        'dbname' => $info['database'],
        'user' => $info['username'],
        'password' => $info['password'],
        'driver' => 'pdo_' . $info['driver'],
      ];
      if (isset($info['host'])) {
        $options['host'] = $info['host'];
      }
      if (isset($info['unix_socket'])) {
        $options['unix_socket'] = $info['unix_socket'];
      }
      if (isset($info['port'])) {
        $options['port'] = $info['port'];
      }
      $this->cache[$target] = DriverManager::getConnection($options, new Configuration());
    }
    return $this->cache[$target];
  }

}
