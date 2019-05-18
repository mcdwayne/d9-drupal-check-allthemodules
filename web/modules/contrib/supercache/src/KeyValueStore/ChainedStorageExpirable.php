<?php

/**
 * @file
 * Contains \Drupal\supercache\KeyValueStore\ChainedStorageExpirable.
 */

namespace Drupal\supercache\KeyValueStore;

use Drupal\Component\Serialization\SerializationInterface;
use Drupal\Core\Database\Query\Merge;
use Drupal\Core\Database\Connection;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;

use Drupal\Core\KeyValueStore\DatabaseStorageExpirable as KeyValueDatabaseStorageExpirable;
use Drupal\Core\KeyValueStore\StorageBase;
use Drupal\Core\KeyValueStore\DatabaseStorage;
use Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\CacheFactoryInterface;
use Drupal\supercache\Cache\DummyTagChecksum;

/**
 * Defines a default key/value store implementation for expiring items.
 *
 * This key/value store implementation uses the database to store key/value
 * data with an expire date.
 */
class ChainedStorageExpirable extends KeyValueDatabaseStorageExpirable implements KeyValueStoreExpirableInterface {

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
  public function __construct(CacheFactoryInterface $factory, $collection, SerializationInterface $serializer, Connection $connection, $table = 'key_value_expire') {
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
   * Retrieve the expiration times for the
   * keys defined in $keys.
   * 
   * @param string[] $keys
   * @return int[]
   */
  public function getExpirations(array $keys) {
    $values = $this->connection->query(
      'SELECT name, expire FROM {' . $this->connection->escapeTable($this->table) . '} WHERE name IN ( :keys[] ) AND collection = :collection',
      array(
        ':keys[]' => $keys,
        ':collection' => $this->collection,
      ))->fetchAllKeyed();
    return $values;
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
        // In order to populate the cache we need to know what the
        // expiration times for each of these key value pairs are,
        // and the DatabaseStorageExpirable implementation does
        // not provide this.
        $this->cache->setMultiple($this->KeyValueToCache($persisted, $this->getExpirations(array_keys($persisted))));
      }
      $this->populateMissingValuesLocal($keys, $persisted);
    }
    $result = $cached + $persisted;
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getAll() {
    // We cannot trust the cache
    // to have everything in it.
    return parent::getAll();
  }

  /**
   * {@inheritdoc}
   */
  function setWithExpire($key, $value, $expire) {
    $this->cache->set($key, $value, REQUEST_TIME + $expire);
    parent::setWithExpire($key, $value, $expire);
  }

  /**
   * {@inheritdoc}
   */
  function setWithExpireIfNotExists($key, $value, $expire) {
    $result = parent::setWithExpireIfNotExists($key, $value, $expire);
    if ($result == Merge::STATUS_INSERT) {
      $this->cache->set($key, $value, REQUEST_TIME + $expire);
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  function setMultipleWithExpire(array $data, $expire) {
    foreach ($data as $key => $value) {
      $this->setWithExpire($key, $value, $expire);
    }
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
