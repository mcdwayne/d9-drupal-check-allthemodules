<?php

/**
 * @file
 * Contains \Drupal\supercache\Cache\DatabaseBackendFactory.
 */

namespace Drupal\supercache\Cache;

use Drupal\Core\Database\Connection;

use Drupal\supercache\Cache\CacheRawFactoryInterface;


class DatabaseRawBackendFactory implements CacheRawFactoryInterface {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Constructs the DatabaseBackendFactory object.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   Database connection
   */
  function __construct(Connection $connection) {
    $this->connection = $connection;
  }

  /**
   * Gets DatabaseBackend for the specified cache bin.
   *
   * @param $bin
   *   The cache bin for which the object is created.
   *
   * @return \Drupal\supercache\Cache\DatabaseRawBackend
   *   The cache backend object for the specified cache bin.
   */
  function get($bin) {
    return new DatabaseRawBackend($this->connection, $bin);
  }

}
