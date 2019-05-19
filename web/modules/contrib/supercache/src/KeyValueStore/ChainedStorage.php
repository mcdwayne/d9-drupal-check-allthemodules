<?php

/**
 * @file
 * Contains \Drupal\supercache\KeyValueStore\ChainedStorage;
 */

namespace Drupal\supercache\KeyValueStore;

use Drupal\Component\Serialization\SerializationInterface;
use Drupal\Core\Database\Query\Merge;
use Drupal\Core\Database\Connection;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;

use Drupal\Core\KeyValueStore\DatabaseStorage as KeyValueDatabaseStorage;
use Drupal\Core\KeyValueStore\StorageBase;
use Drupal\Core\KeyValueStore\KeyValueStoreInterface;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\CacheFactoryInterface;

use Drupal\supercache\Cache\DummyTagChecksum;

/**
 * Defines a chained key value storage that uses
 * any cache backend on top of the database default
 * key/value storage.
 * 
 * This cache backend MUST be a centralized one such
 * as Couchbase, or something that coordinates invalidations
 * such as ChainedFast.
 */
class ChainedStorage extends KeyValueDatabaseStorage implements KeyValueStoreInterface {

  use ChainedStorageTrait;

  /**
   * Overrides Drupal\Core\KeyValueStore\StorageBase::__construct().
   *
   * @param CacheFactoryInterface $factory
   *   The cache backend factory.
   * @param string $collection
   *   The name of the collection holding key and value pairs.
   * @param \Drupal\Component\Serialization\SerializationInterface $serializer
   *   The serialization class to use.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection to use.
   * @param string $table
   *   The name of the SQL table to use, defaults to key_value.
   */
  public function __construct(CacheFactoryInterface $factory, $collection, SerializationInterface $serializer, Connection $connection, $table = 'key_value') {
    parent::__construct($collection, $serializer, $connection, $table);
    // Make sure the collection name passed to the cache factory
    // does not have any dots, or else if using database storage it will
    // crash.
    $sanitized_collection = preg_replace('/[^A-Za-z0-9_]+/', '_', $collection);
    $this->cache = $factory->get($table . '_' . $sanitized_collection);
  }

  /**
   * {@inheritdoc}
   */
  public function has($key) {
    if ($cache = $this->cache->get($key)) {
      if (!empty($this->CacheToKeyValue([$cache]))) {
        return TRUE;
      }
    }
    // The fact that it does not exist in the cache
    // does not mean it does not exist in the database.
    return parent::has($key);
  }

  /**
   * {@inheritdoc}
   */
  public function getMultiple(array $keys) {
    $cached = [];
    if ($cache = $this->cache->getMultiple($keys)) {
      $cached = $this->CacheToKeyValue($cache);
    }
    $persisted = [];
    if (!empty($keys)) {
      $persisted = parent::getMultiple($keys);
      if (!empty($persisted)) {
        $this->cache->setMultiple($this->KeyValueToCache($persisted));
      }
      $this->populateMissingValuesLocal($keys, $persisted);
    }
    $result = array_merge($cached, $persisted);
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getAll() {
    // We have to rely on the persistent
    // storage because we cannot rely
    // on the cache layer to have all
    // the key/value pairs.
    $result = parent::getAll();
    // Do not call set multiple here to prepopulate the cache
    // because it will INVALIDATE the binary on ChainedFast
    // $this->cache->setMultiple($this->KeyValueToCache($result));
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function set($key, $value) {
    $this->cache->set($key, $value);
    parent::set($key, $value);
  }

  /**
   * {@inheritdoc}
   */
  public function setIfNotExists($key, $value) {
    $result = parent::setIfNotExists($key, $value);
    if ($result == Merge::STATUS_INSERT) {
      $this->cache->set($key, $value);
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function rename($key, $new_key) {
    parent::rename($key, $new_key);
    $this->cache->delete($key);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteMultiple(array $keys) {
    parent::deleteMultiple($keys);
    $this->cache->deleteMultiple($keys);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteAll() {
    parent::deleteAll();
    $this->cache->deleteAll();
  }
}
