<?php

/**
 * @file
 * Contains \Drupal\couchbasedrupal\Cache\CouchbaseRawBackend.
 */

namespace Drupal\couchbasedrupal\Cache;

use Drupal\supercache\Cache\CacheRawBackendInterface;
use Drupal\Core\Cache\Cache;
use Drupal\couchbasedrupal\CouchbaseExceptionCodes;
use Drupal\couchbasedrupal\CouchbaseBucket as Bucket;

/**
 * Stores cache items in Couchbase.
 */
class CouchbaseRawBackend extends CouchbaseBackendGeneric implements CacheRawBackendInterface {

  /**
   * Constructs a new CouchbaseBackend instance.
   *
   * @param string $bin
   *   The name of the cache bin.
   * @param string $site_prefix
   *   The prefix to use for all keys in the storage that belong to this site.
   */
  public function __construct($binPrefix, Bucket $bucket) {
    $this->binPrefix = $binPrefix;
    $this->bucket = $bucket;
    $this->view = $this->binPrefix;
    $this->options = array();
    $this->refreshRequestTime();
  }

  /**
   * {@inheritdoc}
   */
  public function get($cid) {
    $cids = array($cid);
    $cache = $this->getMultiple($cids);
    return reset($cache);
  }

  /**
   * {@inheritdoc}
   */
  public function getMultiple(&$cids) {
    $map = array();
    foreach ($cids as $cid) {
      $map[$this->getBinKey($cid)] = $cid;
    }

    $result = $this->bucket->getMultiple(array_keys($map));
    $cache = array();
    foreach ($result as $key => $item) {
      if (empty($item->error)) {
        $item = $this->prepareItem($map[$key], $item);
        if ($item) {
          $cache[$map[$key]] = $item;
        }
      }
    }
    unset($result);

    $cids = array_diff($cids, array_keys($cache));
    return $cache;
  }

  /**
   * Returns all cached items for this binary.
   *
   * @return string[]
   */
  protected function getAllBinary() {
    throw new \Exception("Not implemented.");
  }

  /**
   * Prepares a cached item.
   *
   * @param \stdClass $cache
   *   An item loaded from cache_get() or cache_get_multiple().
   *
   * @return mixed
   *   The cache item or FALSE if the item expired.
   */
  protected function prepareItem($cid, $cache) {
    // This is a workaround to allow the raw
    // backend to hold non scalar values. Because
    // we cannot store metadata when using raw
    // storage we don't know if this was or not
    // a serialized object.
    if (is_string($cache->value) && ($data = @unserialize($cache->value)) && ($data instanceof CouchbaseRawBackendItemHolder)) {
      $cache->value = $data->get();
    }
    return (object) ['cid' => $cid, 'data' => $cache->value];
  }

  /**
   * {@inheritdoc}
   */
  public function set($cid, $data, $expire = CacheRawBackendInterface::CACHE_PERMANENT) {
    if (!is_scalar($data)) {
      $data = serialize(new CouchbaseRawBackendItemHolder($data));
    }

    // Prepare the item.
    if ($expire == CacheRawBackendInterface::CACHE_PERMANENT) {
      return $this->bucket->upsert($this->getBinKey($cid), $data);
    }

    // Fix the expiration before sending this to Couchbase.
    $expire = $this->bucket->FixExpiration($expire);
    return $this->bucket->upsert($this->getBinKey($cid), $data, array('expiry' => $expire));
  }

  /**
   * Like set() but will return false (and fail) if the document already exists.
   */
  public function add($cid, $data, $expire = CacheRawBackendInterface::CACHE_PERMANENT) {
    if (!is_scalar($data)) {
      $data = serialize(new CouchbaseRawBackendItemHolder($data));
    }
    // If you don't pass an INT couchbase crashes.
    $expire = (int) $expire;
    // Prepare the item.
    if ($expire == CacheRawBackendInterface::CACHE_PERMANENT) {
      return $this->bucket->insert($this->getBinKey($cid), $data);
    }
    else {
      return $this->bucket->insert($this->getBinKey($cid), $data, array('expiry' => $expire));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setMultiple(array $items = array()) {
    foreach ($items as $cid => $item) {
      $this->set($cid, $item['data'], isset($item['expire']) ? $item['expire'] : CacheRawBackendInterface::CACHE_PERMANENT);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function delete($cid) {
    $this->bucket->remove($this->getBinKey($cid));
  }

  /**
   * {@inheritdoc}
   */
  public function deleteMultiple(array $cids) {
    $keys = array_map(array($this, 'getBinKey'), $cids);
    $this->bucket->removeMultiple($keys);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteAll() {
    $this->bucket->deleteAllByPrefix($this->binPrefix);
  }

  /**
   * {@inheritdoc}
   */
  public function counter($cid, $increment, $default = 0) {
    $this->counterMultiple([$cid], $increment, $default);
  }

  /**
   * {@inheritdoc}
   */
  public function counterMultiple(array $cids, $increment, $default = 0) {
    $keys = array_map(array($this, 'getBinKey'), $cids);
    $key_map = array_combine($keys, $cids);
    $result = $this->bucket->counter($keys, $increment, array('initial' => $default));
    $result = (array) $result;
    foreach ($result as $key => $item) {
      if (isset($item->error)) {
        if ((string) $item->error->getCode() == CouchbaseExceptionCodes::INCREMENT_REQUESTED_ON_NON_NUMBER) {
          $this->counterSet($key_map[$key], $default);
        }
        else {
          throw $item->error;
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function counterSet($cid, $value) {
    $this->set($cid, (string) $value);
  }

  /**
   * {@inheritdoc}
   */
  public function counterSetMultiple(array $items) {
    foreach ($items as $cid => $item) {
      $this->counterSet($cid, (int) $item);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function counterGet($cid) {
    $cids = [$cid];
    $result = $this->counterGetMultiple($cids);
    return reset($result);
  }

  /**
   * {@inheritdoc}
   */
  public function counterGetMultiple(array &$cids) {
    $results = $this->getMultiple($cids);
    $counters = [];
    foreach ($results as $cid => $item) {
      if (!is_numeric($item->data) && !empty($item->data)) {
        throw new \Exception("Value not valid for counter: $item->data");
      }
      $counters[$cid] = (int) $item->data;
    }
    return $counters;
  }

  /**
   * {@inheritdoc}
   */
  public function removeBin() {
    $this->bucket->deleteAllByPrefix($this->binPrefix);
  }

  /**
   * {@inheritdoc}
   */
  public function garbageCollection() {
    // Nothing to do here.
    // Couchbase automatically handles expirations.
  }

}
