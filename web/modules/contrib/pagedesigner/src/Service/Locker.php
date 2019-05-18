<?php

namespace Drupal\pagedesigner\Service;

use Drupal\user\SharedTempStore;
use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Node locking class.
 *
 * Provides an interface to create and release locks on page nodes.
 */
class Locker
{
    /**
     * The storage for the lock.
     *
     * @var SharedTempStore
     */
    protected $_store = null;

    /**
     * The page key for the lock.
     *
     * @var string
     */
    protected $_key = null;

    /**
     * Create a new instance.
     */
    public function __construct()
    {
        $this->_store = \Drupal::service('user.shared_tempstore')->get('pagedesigner.lock');
        $this->_user = \Drupal::currentUser();
    }

    /**
     * Set the lock key for the entity.
     *
     * @param ContentEntityInterface $id The page id.
     * @param string $lang The page language.
     * @return void
     */
    public function setEntity(ContentEntityInterface $entity)
    {
        $this->_key = 'lock:' . $entity->id() . ':' . $entity->language()->getId();
    }

    /**
     * Return the metda data of the current lock.
     *
     * @return stdClass The meta data.
     */
    public function getMetadata()
    {
        return $this->_store->getMetadata($this->_key);
    }

    /**
     * Return the tab identifier of the current lock.
     *
     * @return string The identifier.
     */
    public function getIdentifier()
    {
        $identifier = $this->_store->get($this->_key);
        return $identifier;
    }

    /**
     * Check whether current user has a lock, false otherwise.
     *
     * @return boolean
     */
    public function hasLock()
    {
        if ($this->_store->getIfOwner($this->_key) !== null) {
            return true;
        }
        return false;
    }

    /**
     * Acquire a lock.
     *
     * Acquires a lock for the current user using the page information and (optionally) an identifier.
     * If the identifier is provided, it must match the existing lock or no lock must be present.
     *
     * @param string $identifier
     * @return boolean
     */
    public function acquire($identifier = null)
    {
        // Create if key does not exist or update the lock if user is owner
        if ($this->_createLock($identifier)) {
            return true;
        }
        // Key exists, but user is not owner
        // Check if lock has expired. If so, delete and create
        $data = $this->_store->get($this->_key);
        if ($data !== null && $this->_store->getMetadata($this->_key)->updated < time() - 900) {
            $this->_store->delete($this->_key);
            return $this->_createLock($identifier);
        }

        // Valid lock of other user found
        return false;
    }

    /**
     * Release the lock.
     *
     * Release the lock of the current user, optionally matching the identifier.
     * If the identifier is provided, only locks having the identifier will be released.
     *
     * @param string $identifier
     * @return void
     */
    public function release($identifier = null)
    {
        $data = $this->_store->getIfOwner($this->_key);
        if ($data !== null) {
            if ($identifier !== null) {
                if ($data == $identifier) {
                    return $this->_store->deleteIfOwner($this->_key);
                }
            } else {
                return $this->_store->deleteIfOwner($this->_key);
            }
        }
        return false;
    }

    /**
     * Helper function to create locks.
     *
     * @param string $identifier
     * @return void
     */
    protected function _createLock($identifier)
    {
        $lockIdentifier = $this->_store->getIfOwner($this->_key);
        if ($lockIdentifier !== null && $lockIdentifier !== $identifier) {
            return false;
        }
        if (!$this->_store->setIfOwner($this->_key, $identifier)) {
            return $this->_store->setIfNotExists($this->_key, $identifier);
        }
        return true;
    }
}
