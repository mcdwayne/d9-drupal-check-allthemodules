<?php

/**
 * @file
 * Contains \Drupal\supercache\KeyValueStore\KeyValueWincacheExpirableFactory.
 */

namespace Drupal\supercache\KeyValueStore;

use Drupal\Component\Serialization\SerializationInterface;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\CacheFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Database;

use Drupal\Core\KeyValueStore\KeyValueExpirableFactoryInterface;

/**
 * Defines the key/value store factory for the wincache/databse backend.
 */
class KeyValueChainedExpirableFactory implements KeyValueExpirableFactoryInterface {

  /**
   * Holds references to each instantiation so they can be terminated.
   *
   * @var \Drupal\Core\KeyValueStore\DatabaseStorageExpirable[]
   */
  protected $storages = array();

  /**
   * The serialization class to use.
   *
   * @var \Drupal\Component\Serialization\SerializationInterface
   */
  protected $serializer;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The cache factory.
   * 
   * @var CacheFactoryInterface
   */
  protected $factory;

  /**
   * Constructs this factory object.
   *
   * @param \Drupal\Component\Serialization\SerializationInterface $serializer
   *   The serialization class to use.
   * @param \Drupal\Core\Database\Connection $connection
   *   The Connection object containing the key-value tables.
   */
  function __construct(CacheFactoryInterface $factory, SerializationInterface $serializer, Connection $connection) {
    $this->serializer = $serializer;
    $this->connection = $connection;
    $this->factory = $factory;
  }

  protected $backed_by_database;

  /**
   * Not very reliabled but....
   * we want to prevent this thing
   * from triggering if the cache
   * factory is going to return
   * us a cache backend that sits
   * on top of the database...
   */
  function backedByDatabase() {
    if (!isset($this->backed_by_database)) {
      $this->backed_by_database = $this->factory->get('test-binary') instanceof \Drupal\Core\Cache\DatabaseBackend;
    }
    return $this->backed_by_database;
  }

  /**
   * {@inheritdoc}
   */
  public function get($collection) {
    if (!isset($this->storages[$collection])) {
      // Do not chain if the database will
      // be taking care of caching.
      if ($this->backedByDatabase()) {
        return new \Drupal\Core\KeyValueStore\DatabaseStorageExpirable($collection, $this->serializer, $this->connection);
      }
      $this->storages[$collection] = new ChainedStorageExpirable($this->factory, $collection, $this->serializer, $this->connection);
    }
    return $this->storages[$collection];
  }

  /**
   * Deletes expired items.
   */
  public function garbageCollection() {
    // No need to gargabe collect.
  }
}
