<?php

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

trait ChainedStorageTrait {

  /**
   * The cache backend.
   *
   * @var CacheBackendInterface
   */
  protected $cache;

  /**
   * Special value stored in the cache layer
   * to identify missing items in the persistent
   * cache.
   * 
   * @var string
   */
  protected $EMPTY_VALUE = '@@empty';

  /**
   * To prevent database lookups we store a special 'empty'
   * object.
   *
   * @param array $requested
   *   The requested set of keys from getMultiple().
   *
   * @param array $obtained
   *   The effectively obtained key-value pairs.
   *
   */
  protected function populateMissingValuesLocal($requested, $obtained) {
    $missing = array_diff_key(array_flip($requested), $obtained);
    foreach ($missing as $key => $value) {
      $missing[$key] = $this->EMPTY_VALUE;
    }
    $this->cache->setMultiple($this->KeyValueToCache($missing));
  }

  /**
   * Converts an array of KeyValues to a cache compatible array.
   *
   * @param array $items
   *   Key/Value pair items to store.
   * @param array $items
   *   Expiration times for the items.
   * @return array
   */
  protected function KeyValueToCache(array $items, array $expirations = []) {
    $result = array();
    foreach ($items as $key => $value) {
      $result[$key] = array(
          'data' => $value,
          'expire' => isset($expirations[$key]) ? $expirations[$key] : CacheBackendInterface::CACHE_PERMANENT,
          'tags' => array(),
        );
    }
    return $result;
  }

  /**
   * Converts a cache array to a KeyValue array.
   *
   * @param array $items
   * @return array
   */
  protected function CacheToKeyValue(array $items) {
    $result = array();
    foreach ($items as $key => $value) {
      if (is_object($value) && $value->data !== $this->EMPTY_VALUE) {
        $result[$key] = $value->data;
      }
    }
    return $result;
  }
}
