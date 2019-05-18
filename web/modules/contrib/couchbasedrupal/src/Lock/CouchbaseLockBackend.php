<?php

/**
 * @file
 * Contains \Drupal\couchbasedrupal\Lock\CouchbaseLockBackend.
 */

namespace Drupal\couchbasedrupal\Lock;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\IntegrityConstraintViolationException;

use Drupal\couchbasedrupal\Cache\CouchbaseRawBackend;
use Drupal\couchbasedrupal\Cache\CouchbaseRawBackendFactory;
use Drupal\couchbasedrupal\CouchbaseManager;

use Drupal\Core\Lock\LockBackendAbstract;

use Drupal\supercache\Cache\DummyTagChecksum;

/**
 * Defines the database lock backend. This is the default backend in Drupal.
 *
 * @ingroup lock
 */
class CouchbaseLockBackend extends LockBackendAbstract {

  /**
   * Cache storage.
   *
   * @var CouchbaseRawBackend
   */
  protected $cache;

  /**
   * Constructs a new CouchbaseLockBackend.
   *
   */
  public function __construct(CouchbaseRawBackendFactory $factory) {
    // __destruct() is causing problems with garbage collections, register a
    // shutdown function instead.
    drupal_register_shutdown_function(array($this, 'releaseAll'));
    $this->cache = $factory->get('semaphore');
    // Initialize the lock id.
    $this->getLockId();
  }

  /**
   * {@inheritdoc}
   */
  public function acquire($name, $timeout = 30.0) {
    // Ensure that the timeout is at least 1 s.
    $timeout = max($timeout, 1);
    $expire = (int) microtime(TRUE) + $timeout;

    if ($this->cache->add($name, $this->lockId, $expire)) {
      $this->locks[$name] = $this->lockId;
    }
    elseif (($result = $this->cache->get($name)) && isset($this->locks[$name]) && $this->locks[$name] == $result->data) {
      // Only renew the lock if we already set it and it has not expired.
      $this->cache->set($name, $this->lockId, $expire);
    }
    else {
      // Failed to acquire the lock.  Unset the key from the $locks array even if
      // not set, PHP 5+ allows this without error or warning.
      unset($this->locks[$name]);
    }

    return isset($this->locks[$name]);
  }

  /**
   * {@inheritdoc}
   */
  public function lockMayBeAvailable($name) {
    return !$this->cache->get($name);
  }

  /**
   * {@inheritdoc}
   */
  public function release($name) {
    $result = $this->cache->get($name);
    if (isset($this->locks[$name]) && $result !== FALSE && $result->data == $this->locks[$name]) {
      $this->cache->delete($name);
    }
    // We unset unconditionally since caller assumes lock is released anyway.
    unset($this->locks[$name]);
  }

  /**
   * {@inheritdoc}
   */
  public function releaseAll($lockId = NULL) {
    // Only attempt to release locks if any were acquired.
    if (!empty($this->locks)) {
      if (empty($lockId)) {
        $lockId = $this->getLockId();
      }
      $clears = array();
      foreach ($this->locks as $name => $id) {
        if ($cache = $this->cache->get($name)) {
          $value = $cache->data;
          if ($value == $id) {
            $clears[] = $name;
          }
        }
      }
      $this->locks = array();
      $this->cache->deleteMultiple($clears);
    }
  }
}
