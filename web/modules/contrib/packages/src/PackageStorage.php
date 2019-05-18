<?php

namespace Drupal\packages;

use Drupal\packages\PackageState;
use Drupal\packages\PackageStorageException;
use Drupal\Core\Database\Driver\mysql\Connection;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Class PackageStorage.
 *
 * Handles CRUD operations for per-user package settings.
 *
 * @package Drupal\packages
 */
class PackageStorage implements PackageStorageInterface {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Driver\mysql\Connection
   */
  protected $database;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * The user entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $userStorage;

  /**
   * The user ID to handle package storage for.
   *
   * @var int
   */
  protected $userId;

  /**
   * The database table.
   *
   * @var string
   */
  const TABLE = 'packages';

  /**
   * The static cache key.
   *
   * @var string
   */
  const STATIC_CACHE_KEY = 'package_storage';

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Database\Driver\mysql\Connection $database
   *   A database connection.
   * @param \Drupal\Core\Session\AccountProxy $current_user
   *   The current user.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager.
   */
  public function __construct(Connection $database, AccountProxy $current_user, EntityTypeManagerInterface $entity_type_manager) {
    $this->database = $database;
    $this->currentUser = $current_user;
    $this->userStorage = $entity_type_manager->getStorage('user');

    // Set the current user ID.
    $this->setUserId();
  }

  /**
   * {@inheritdoc}
   */
  public function setUserId($user_id = NULL) {
    // Use the current user's ID if one was not provided.
    $this->userId = $user_id ? $user_id : $this->currentUser->id();

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getUserId() {
    return $this->userId;
  }

  /**
   * {@inheritdoc}
   */
  public function delete() {
    // Don't bother deleting for anonymous users.
    if ($this->userId <= 0) {
      return;
    }

    // Delete the states from the database.
    $this->database
      ->delete(self::TABLE)
      ->condition('uid', $this->userId)
      ->execute();

    // Invalidate the cache for the user.
    $this->invalidateCache();

    // Remove the states in the static cache.
    $cache = &drupal_static(self::STATIC_CACHE_KEY, []);
    unset($cache[$this->userId]);
  }

  /**
   * {@inheritdoc}
   */
  public function load() {
    $states = &drupal_static(self::STATIC_CACHE_KEY, []);

    // Don't bother loading for anonymous users.
    if ($this->userId <= 0) {
      return [];
    }

    // Check if states have not been loaded for this user.
    if (!isset($states[$this->userId])) {
      // Load the states from the database.
      $result = $this->database
        ->select(self::TABLE, 'p')
        ->fields('p', ['states'])
        ->condition('uid', $this->userId)
        ->execute()
        ->fetchField();

      // Set the static cache.
      $states[$this->userId] = $result ? unserialize($result) : [];
    }

    return $states[$this->userId];
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $states) {
    // User IDs must be greater than zero.
    if ($this->userId <= 0) {
      throw new PackageStorageException("User Id must be greater than zero for package storage.");
    }

    // Validate the states.
    foreach ($states as $state) {
      if (!is_object($state) || !($state instanceof PackageState)) {
        throw new PackageStorageException("Only an array of PackageState objects can be saved.");
      }
    }

    // Save the states.
    $this->database
      ->merge(self::TABLE)
      ->key(['uid' => $this->userId])
      ->fields([
        'uid' => $this->userId,
        'states' => serialize($states),
      ])
      ->execute();

    // Invalidate the cache for the user.
    $this->invalidateCache();

    // Store the states in the static cache.
    $cache = &drupal_static(self::STATIC_CACHE_KEY, []);
    $cache[$this->userId] = $states;
  }

  /**
   * Invalidate the cache for the set user.
   */
  private function invalidateCache() {
    // Load the user.
    if ($user = $this->userStorage->load($this->userId)) {
      // Invalidate the cache for this user.
      Cache::invalidateTags($user->getCacheTags());
    }
  }

}
