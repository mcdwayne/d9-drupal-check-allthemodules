<?php

namespace Drupal\consistent_uid\HookHandler;

use Drupal\Core\Database\Connection;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\Core\State\StateInterface;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Updates the current user's last access time.
 */
class ConsistentUidHookHandler implements ContainerInjectionInterface {

  const INCREMENT = 'consistent_uid.increment';
  const LOCK = 'consistent_uid.lock';

  /**
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * State service.
   *
   * @var \Drupal\workflows\StateInterface
   */
  protected $state;

  /**
   * Locker service.
   *
   * @var \Drupal\Core\Lock\LockBackendInterface
   */
  protected $lockBackend;

  /**
   * MiUsersHookHandler constructor.
   */
  public function __construct(Connection $database,
                              StateInterface $state,
                              LockBackendInterface $lockBackend) {

    $this->database = $database;
    $this->state = $state;
    $this->lockBackend = $lockBackend;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {

    return new static(
      $container->get('database'),
      $container->get('state'),
      $container->get('lock')
    );
  }

  /**
   * Attempts to handle deadlocks.
   */
  public function acquireLock() {

    $acquired = FALSE;
    while (!$acquired) {
      try {
        while (!($acquired = $this->lockBackend->acquire(static::LOCK))) {
          $this->lockBackend->wait(static::LOCK);
        }
      } catch (\Exception $e) { }
    }
  }

  /**
   * Attempts to handle deadlocks.
   */
  public function releaseLock() {

    $released = FALSE;
    while (!$released) {
      try {
        $this->lockBackend->release(static::LOCK);
        $released = TRUE;
      } catch (\Exception $e) { }
    }
  }

  /**
   * Implements Implements hook_ENTITY_TYPE_presave().
   */
  public function hookUserPreSave(EntityInterface $entity) {

    // Aim is to escape User Storage nextId() method call.
    // So we set 'uid' explicit according to DB for user with this set to null.
    // We do not use isNew() check to keep possibility save new users with
    // predefined id (like system '0' or '1').
    // Also we keep count for used ids in state variable to ensure that
    // each new user has unique new incremented id.
    if ($entity instanceof UserInterface && is_null($entity->id())) {
      // Thread safe locking for ensuring correct user id.
      $this->acquireLock();
      // Use try/finally to guarantee lock release.
      try {
        // Lock acquired.
        $userStateIncrement = $this->state->get(static::INCREMENT, 0);
        $userDBIncrement = $this->database->query('SELECT MAX(uid) FROM {users}')
          ->fetchField();
        $userDBIncrement = $userDBIncrement ? (int) $userDBIncrement : 0;
        if ($userStateIncrement < $userDBIncrement) {
          $userIncrement = $userDBIncrement + 1;
        }
        else {
          $userIncrement = $userStateIncrement + 1;
        }
        $entity->set('uid', $userIncrement);
        $this->state->set(static::INCREMENT, $userIncrement);
      }
      finally {
        // Release lock.
        $this->releaseLock();
      }
    }
  }

}
