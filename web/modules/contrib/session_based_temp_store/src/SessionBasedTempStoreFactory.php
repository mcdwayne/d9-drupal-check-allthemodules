<?php

namespace Drupal\session_based_temp_store;

use Drupal\Core\KeyValueStore\KeyValueExpirableFactoryInterface;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\Core\Session\SessionConfigurationInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Creates a SessionBasedTempStore object for a given collection.
 */
class SessionBasedTempStoreFactory {

  /**
   * The storage factory creating the backend to store the data.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueExpirableFactoryInterface
   */
  protected $storageFactory;

  /**
   * The lock object used for this data.
   *
   * @var \Drupal\Core\Lock\LockBackendInterface
   */
  protected $lockBackend;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The session configuration.
   *
   * @var \Drupal\Core\Session\SessionConfigurationInterface
   */
  protected $sessionConfiguration;

  /**
   * The time to live for items in seconds.
   *
   * @var int
   */
  protected $expire;

  /**
   * Constructs a SessionBasedTempStoreFactory object.
   *
   * @param \Drupal\Core\KeyValueStore\KeyValueExpirableFactoryInterface $storage_factory
   *   The key/value store factory.
   * @param \Drupal\Core\Lock\LockBackendInterface $lock_backend
   *   The lock object used for this data.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\Session\SessionConfigurationInterface $session_configuration
   *   The session configuration interface.
   * @param int $expire
   *   The time to live for items, in seconds.
   */
  public function __construct(KeyValueExpirableFactoryInterface $storage_factory, LockBackendInterface $lock_backend, RequestStack $request_stack, SessionConfigurationInterface $session_configuration, $expire) {
    $this->storageFactory = $storage_factory;
    $this->lockBackend = $lock_backend;
    $this->requestStack = $request_stack;
    $this->sessionConfiguration = $session_configuration;
    $this->expire = $expire;
  }

  /**
   * Creates a PrivateTempStore.
   *
   * @param string $collection
   *   The collection name to use for this key/value store. This is typically
   *   a shared namespace or module name, e.g. 'views', 'entity', etc.
   * @param int $expire
   *   The time to live for items in the collection, in seconds.
   * @param string $path
   *   The path on the server in which the cookie will be available on.
   *   If set to '/', the cookie will be available within the entire domain.
   *   If set to '/foo/', the cookie will only be available
   *   within the /foo/ directory and all sub-directories such as /foo/bar/
   *   of domain. By default the value is to '/'.
   *
   * @return \Drupal\session_based_temp_store\SessionBasedTempStore
   *   An instance of the key/value store.
   */
  public function get($collection, $expire = NULL, $path = '/') {
    // Allow expire to be set per collection, use default if not provided.
    $expire = $expire ?? $this->expire;
    // Store the data for this collection in the database.
    $storage = $this->storageFactory->get("user.private_tempstore.$collection");
    return new SessionBasedTempStore($storage, $this->lockBackend, $this->requestStack, $this->sessionConfiguration, $expire, $path);
  }

}
