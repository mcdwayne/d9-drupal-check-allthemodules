<?php

/**
 * @file
 * Definition of Drupal\autoslave\Lock\AutoSlaveLockBackend.
 */

namespace Drupal\autoslave\Lock;

use Drupal\Core\Lock\LockBackendInterface;
use Drupal\Core\Database\Database;

/**
 * Defines the database lock backend. This is the default backend in Drupal.
 */
class AutoSlaveLockBackend implements LockBackendInterface {
  protected $locks = array();

  protected $backend;

  function __construct() {
    global $conf;
    $backend = isset($conf['autoslave_lock_class']) ? $conf['autoslave_lock_class'] : 'Drupal\Core\Lock\DatabaseLockBackend';
    $this->backend = new $backend();
  }

  private function forceMaster($value) {
    $conn = Database::getConnection();
    if ($conn->driver() == 'autoslave') {
      $conn->forceMaster($value);
    } 
  }

  /**
   * Implements Drupal\Core\Lock\LockBackedInterface::acquire().
   */
  public function acquire($name, $timeout = 30.0) {
    $already_acquired = isset($this->locks[$name]);
    if ($result = $this->backend->acquire($name, $timeout)) {
      $this->locks[$name] = TRUE;
      // Force master
      if (!$already_acquired) {
        $this->forceMaster(1);
      }
    }
    return $result;
  }

  /**
   * Implements Drupal\Core\Lock\LockBackedInterface::wait().
   */
  public function wait($name, $delay = 30) {
    return $this->backend->wait($name, $delay);
  }

  /**
   * Implements Drupal\Core\Lock\LockBackedInterface::lockMayBeAvailable().
   */
  public function lockMayBeAvailable($name) {
    return $this->backend->lockMayBeAvailable($name);
  }

  /**
   * Implements Drupal\Core\Lock\LockBackedInterface::release().
   */
  public function release($name) {
    if (isset($this->locks[$name])) {
      unset($this->locks[$name]);
      // Unforce master
      $this->forceMaster(-1);
    }
    return $this->backend->release($name);
  }

  /**
   * Implements Drupal\Core\Lock\LockBackedInterface::releaseAll().
   */
  public function releaseAll($lock_id = NULL) {
    $this->locks = array();
    // Unfore master
    $this->forceMaster(-count($this->locks));
    return $this->backend->releaseAll($lock_id);
  }

  /**
   * Implements Drupal\Core\Lock\LockBackedInterface::getLockId().
   */
  public function getLockId() {
    return $this->backend->getLockId();
  }
}
