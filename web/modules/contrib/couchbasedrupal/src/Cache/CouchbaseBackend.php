<?php

/**
 * @file
 * Contains \Drupal\couchbasedrupal\Cache\CouchbaseBackend.
 */

namespace Drupal\couchbasedrupal\Cache;

use Drupal\couchbasedrupal\CouchbaseBucket;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\CacheTagsChecksumInterface;
use Drupal\Core\Cache\Cache;
use Drupal\couchbasedrupal\CouchbaseExceptionCodes;

/**
 * Stores cache items in Couchbase.
 */
class CouchbaseBackend extends CouchbaseBackendGeneric implements CacheBackendInterface {

  /**
   * Some couchbase exceptions are "permissible" because
   * they cannot be worked around
   * such as a cache item being too big. Treat them as cache
   * misses or silent cache set fails.
   *
   * @var string[]
   */
  protected $ignore_exception_codes = [CouchbaseExceptionCodes::INVALID_PACKET,
    CouchbaseExceptionCodes::OBJECT_TOO_BIG];

  /**
   * Couchbase bucket
   *
   * @var \Drupal\couchbasedrupal\CouchbaseBucket
   */
  protected $bucketPassthrough;

  /**
   * Last time this cache was cleared.
   *
   * @var float
   */
  protected $lastClearTime = 0;

  /**
   * The cache tags checksum provider.
   *
   * @var \Drupal\Core\Cache\CacheTagsChecksumInterface
   */
  protected $checksumProvider;

  /**
   * Expiration for permanent items.
   *
   * @var int
   */
  protected $cache_lifetime;

  /**
   * Get or Set the last clear time.
   *
   * @param float $time
   */
  protected function LastClear($time = NULL) {
    $key = 'BinaryLastCleared|' . $this->binPrefix;
    if (empty($time)) {
      if ($cache = $this->bucketPassthrough->get($key)) {
        $this->lastClearTime = $cache->value ?? 0;
      }
      else {
        $this->bucket->insert($key, 0);
        $this->lastClearTime = 0;
      }
    }
    else {
      $this->bucket->upsert($key, $time);
      $this->lastClearTime = $time;
    }
  }

  /**
   * Constructs a new CouchbaseBackend instance.
   *
   * @param string $bin
   *   The name of the cache bin.
   * @param string $site_prefix
   *   The prefix to use for all keys in the storage that belong to this site.
   * @param \Drupal\Core\Cache\CacheTagsChecksumInterface $checksum_provider
   *   The cache tags checksum provider.
   */
  public function __construct($binPrefix, CacheTagsChecksumInterface $checksum_provider, CouchbaseBucket $bucket, CouchbaseBucket $bucketPassthrough) {
    $this->checksumProvider = $checksum_provider;
    $this->binPrefix = $binPrefix;
    $this->bucket = $bucket;
    $this->bucketPassthrough = $bucketPassthrough;
    $this->view = $this->binPrefix;
    $this->options = [];
    $this->LastClear();
    $this->refreshRequestTime();
    // Hardcoded to 15 days at the moment,  should be good to read
    // this from some settings.
    $this->cache_lifetime = 3600 * 24 * 15;
  }

  /**
   * Fix the expiration: make adaptations for the couchbase relative/absolute
   * threshold + add an expiration for permanent items.
   *
   * @param int $expiration
   *   The expiration as a Unix Timestamp.
   */
  protected function fixExpiration($expire) {
    // Add an expiration for permanent items....
    if ($expire == CacheBackendInterface::CACHE_PERMANENT) {
      // There is no such thing as a "permanent" item, or there is risk of stuff
      // staying endlessly in the cache... use the default lifetime plus a 10% variation.
      $expire = time() + ($this->cache_lifetime) + rand(0, $this->cache_lifetime * 0.1);
    }
    return $this->bucket->FixExpiration($expire);
  }

  /**
   * {@inheritdoc}
   */
  public function get($cid, $allow_invalid = FALSE) {
    $cids = [$cid];
    $cache = $this->getMultiple($cids, $allow_invalid);
    return reset($cache);
  }

  /**
   * {@inheritdoc}
   */
  public function getMultiple(&$cids, $allow_invalid = FALSE) {
    // Translate the requested cache item IDs to Wincache keys.
    $map = [];
    foreach ($cids as $cid) {
      $map[$this->getBinKey($cid)] = $cid;
    }

    $result = $this->bucket->getMultiple(array_keys($map));
    $cache = [];
    foreach ($result as $key => $item) {
      $item = $this->prepareItem($item, $allow_invalid);
      if ($item) {
        $cache[$map[$key]] = $item;
      }
    }
    unset($result);

    $cids = array_diff($cids, array_keys($cache));
    return $cache;
  }

  /**
   * Prepares a cached item.
   *
   * Checks that the item is either permanent or did not expire.
   *
   * @param \Couchbase\Document $doc
   *   An item loaded from cache_get() or cache_get_multiple().
   * @param bool $allow_invalid
   *   If TRUE, a cache item may be returned even if it is expired or has been
   *   invalidated. See ::get().
   *
   * @return mixed
   *   The cache item or FALSE if the item expired.
   */
  protected function prepareItem(\Couchbase\Document $doc, $allow_invalid = FALSE) {
    if ($doc->error && $code = $doc->error->getCode()) {
      if ($code == CouchbaseExceptionCodes::KEY_DOES_NOT_EXIST) {
        return FALSE;
      }
      throw $doc->error;
    }

    $cache = $doc->value;
    if (!isset($cache->data)) {
      return FALSE;
    }

    if ($cache->created <= $this->lastClearTime && $allow_invalid == FALSE) {
      return FALSE;
    }

    $cache->tags = $cache->tags ? explode(' ', $cache->tags) : [];

    // Check expire time.
    $cache->valid = $cache->expire == CacheBackendInterface::CACHE_PERMANENT || $cache->expire >= $this->requestTime;

    // Check if invalidateTags() has been called with any of the entry's tags.
    if (!$this->checksumProvider->isValid($cache->checksum, $cache->tags)) {
      $cache->valid = FALSE;
    }

    if (!$allow_invalid && !$cache->valid) {
      return FALSE;
    }

    // Bad document i.e. when unserialization fails, i.e. when
    // the environment has changed and igbinary is not available anymore.
    if ($cache->data instanceof CouchbaseCacheTranscoderBadDocument) {
      return FALSE;
    }

    return $cache;
  }

  /**
   * Prepares an item to be stored in cache.
   *
   *
   *
   * @param mixed $cid
   * @param mixed $data
   * @param mixed $expire
   * @param array $tags
   * @return \stdClass
   */
  protected function prepareCacheItem($cid, $data, $expire, array $tags = []) {
    Cache::validateTags($tags);
    $tags = array_unique($tags);
    $cache = new \stdClass();
    $cache->cid = $cid;
    $cache->binPrefix = $this->binPrefix;
    $cache->binKey = $this->getBinKey($cid);
    $cache->created = round(microtime(TRUE), 3);
    $cache->expire = $expire;
    $cache->tags = implode(' ', $tags);
    $cache->checksum = $this->checksumProvider->getCurrentChecksum($tags);
    $cache->data = $data;
    return $cache;
  }

  /**
   * {@inheritdoc}
   */
  public function set($cid, $data, $expire = CacheBackendInterface::CACHE_PERMANENT, array $tags = []) {
    $cache = $this->prepareCacheItem($cid, $data, $expire, $tags);
    $expire = $this->FixExpiration($expire);
    try {
      return $this->bucket->upsert($this->getBinKey($cid), $cache, ['expiry' => $expire]);
    }
    catch (\Exception $e) {
      if (in_array((string) $e->getCode(), $this->ignore_exception_codes)) {
        return FALSE;
      }
      throw $e;
    }
  }

  /**
   * Like set() but will return false (and fail) if the document already exists.
   */
  public function add($cid, $data, $expire = CacheBackendInterface::CACHE_PERMANENT, array $tags = []) {
    // If you don't pass an INT couchbase crashes.
    $expire = (int) $expire;
    // Prepare the item.
    $cache = $this->prepareCacheItem($cid, $data, $expire, $tags);
    $expire = $this->fixExpiration($expire);
    try {
      $this->bucket->insert($this->getBinKey($cid), $cache, array('expiry' => $expire));
    }
    catch (\Exception $e) {
      if (in_array((string) $e->getCode(), $this->ignore_exception_codes)) {
        // Passthrough.
        return FALSE;
      }
      throw $e;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setMultiple(array $items = []) {
    foreach ($items as $cid => $item) {
      $this->set($cid, $item['data'], isset($item['expire']) ? $item['expire'] : CacheBackendInterface::CACHE_PERMANENT, isset($item['tags']) ? $item['tags'] : []);
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
    $keys = array_map([$this, 'getBinKey'], $cids);
    $this->bucket->removeMultiple($keys);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteAll() {
    $prefix = $this->getBinKey('');
    $this->bucket->deleteAllByPrefix($prefix);
  }

  /**
   * {@inheritdoc}
   */
  public function removeBin() {
    $this->LastClear(microtime(TRUE));
  }

  /**
   * {@inheritdoc}
   */
  public function invalidate($cid) {
    $this->invalidateMultiple(array($cid));
  }

  /**
   * {@inheritdoc}
   */
  public function invalidateMultiple(array $cids) {
    // Because we do not support serving
    // expired items... let's simply delete
    // them all.
    $this->deleteMultiple($cids);
  }

  /**
   * {@inheritdoc}
   */
  public function invalidateAll() {
    $this->removeBin();
  }

  /**
   * {@inheritdoc}
   */
  public function garbageCollection() {
    // No need to do this...
    return;

  }

}
