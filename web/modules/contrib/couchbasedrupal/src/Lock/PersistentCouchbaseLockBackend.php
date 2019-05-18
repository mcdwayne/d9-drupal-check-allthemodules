<?php

/**
 * @file
 * Contains \Drupal\couchbasedrupal\Lock\PersistentCouchbaseLockBackend.
 */

namespace Drupal\couchbasedrupal\Lock;

use Drupal\couchbasedrupal\Cache\CouchbaseRawBackend;
use Drupal\couchbasedrupal\Cache\CouchbaseRawBackendFactory;
use Drupal\supercache\Cache\DummyTagChecksum;

use Drupal\Core\Database\Connection;

/**
 * Defines the persistent database lock backend. This backend is global for this
 * Drupal installation.
 *
 * @ingroup lock
 */
class PersistentCouchbaseLockBackend extends CouchbaseLockBackend {

  /**
   * Constructs a new PersistentDatabaseLockBackend.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   */
  public function __construct(CouchbaseRawBackendFactory $factory) {
    // Do not call the parent constructor to avoid registering a shutdown
    // function that releases all the locks at the end of a request.
    $this->cache = $factory->get('semaphore');
    // Set the lockId to a fixed string to make the lock ID the same across
    // multiple requests. The lock ID is used as a page token to relate all the
    // locks set during a request to each other.
    // @see \Drupal\Core\Lock\LockBackendInterface::getLockId()
    $this->lockId = 'persistent';
  }
}
