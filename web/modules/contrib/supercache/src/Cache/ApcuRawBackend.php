<?php

/**
 * @file
 * Contains \Drupal\supercache\Cache\ApcuRawBackend.
 */

namespace Drupal\supercache\Cache;

/**
 * Stores cache items in the Alternative PHP Cache User Cache (APCu).
 */
class ApcuRawBackend implements CacheRawBackendInterface {

  use RequestTimeTrait;

  /**
   * The Cache API uses a unixtimestamp to set
   * expiration. But APC expects a TTL.
   * 
   * @param int $expire
   */
  protected function getTtl($expire) {
    if ($expire == CacheRawBackendInterface::CACHE_PERMANENT) {
      // If no ttl is supplied (or if the ttl is 0), the value will persist until it is removed from the cache manually, 
      // or otherwise fails to exist in the cache (clear, restart, etc.).
      return FALSE;
    }
    $result = $expire - time();
    if ($result < 0) {
      return 1;
    }
    return $result;
  }

  /**
   * Store a value using apcu_store and adjusting
   * the $expire timestamp to a TTL.
   *
   * @param string $cid
   *   The cache id.
   * @param mixed $data
   *   An item to store in the cache.
   * @param int $expire
   *   The unix timestamp at which this item will expire.
   */
  protected function apcSet($cid, $data, $expire) {
    if ($ttl = $this->getTtl($expire)) {
      return apcu_store($cid, $data, $ttl);
    }
    else {
      return apcu_store($cid, $data);
     }
  }

  /**
   * The name of the cache bin to use.
   *
   * @var string
   */
  protected $bin;

  /**
   * Prefix for all keys in the storage that belong to this site.
   *
   * @var string
   */
  protected $sitePrefix;

  /**
   * Prefix for all keys in this cache bin.
   *
   * Includes the site-specific prefix in $sitePrefix.
   *
   * @var string
   */
  protected $binPrefix;

  /**
   * Constructs a new ApcuBackend instance.
   *
   * @param string $bin
   *   The name of the cache bin.
   * @param string $site_prefix
   *   The prefix to use for all keys in the storage that belong to this site.
   * @param \Drupal\Core\Cache\CacheTagsChecksumInterface $checksum_provider
   *   The cache tags checksum provider.
   */
  public function __construct($bin, $site_prefix) {
    $this->bin = $bin;
    $this->sitePrefix = $this->shortMd5($site_prefix);
    $this->binPrefix = $this->sitePrefix . ':' . $this->bin . ':';
  }

  /**
   * Prepends the APC user variable prefix for this bin to a cache item ID.
   *
   * @param string $cid
   *   The cache item ID to prefix.
   *
   * @return string
   *   The APCu key for the cache item ID.
   */
  public function getApcuKey($cid) {
    return $this->binPrefix . $cid;
  }

  /**
   * {@inheritdoc}
   */
  public function get($cid) {
    $success = FALSE;
    $data = apcu_fetch($this->getApcuKey($cid), $success);
    if (!$success) {
      return FALSE;
    }
    return $this->prepareItem($cid, $data);
  }

  /**
   * {@inheritdoc}
   */
  public function getMultiple(&$cids) {
    // Translate the requested cache item IDs to APCu keys.
    $map = array();
    foreach ($cids as $cid) {
      $map[$this->getApcuKey($cid)] = $cid;
    }

    $result = apcu_fetch(array_keys($map));
    $cache = array();
    if ($result) {
      foreach ($result as $key => $item) {
        $item = $this->prepareItem($key, $item);
        $cache[$map[$key]] = $item;
      }
    }
    unset($result);

    $cids = array_diff($cids, array_keys($cache));
    return $cache;
  }

  /**
   * Returns all cached items, optionally limited by a cache ID prefix.
   *
   * APCu is a memory cache, shared across all server processes. To prevent
   * cache item clashes with other applications/installations, every cache item
   * is prefixed with a unique string for this site. Therefore, functions like
   * apcu_clear_cache() cannot be used, and instead, a list of all cache items
   * belonging to this application need to be retrieved through this method
   * instead.
   *
   * @param string $prefix
   *   (optional) A cache ID prefix to limit the result to.
   *
   * @return \APCIterator
   *   An APCIterator containing matched items.
   */
  protected function getAll($prefix = '') {
    return new \APCIterator('user', '/^' . preg_quote($this->getApcuKey($prefix), '/') . '/');
  }

  /**
   * Prepares a cached item.
   *
   * Checks that the item is either permanent or did not expire.
   *
   * @param string $cid
   *   The cache id.
   * @param mixed $data
   *   An item retrieved from the cache.
   *
   * @return \stdClass
   *   The cache item as a Drupal cache object.
   */
  protected function prepareItem($cid, $data) {
    return (object) array('data' => $data, 'cid' => $cid);
  }

  /**
   * {@inheritdoc}
   */
  public function set($cid, $data, $expire = CacheRawBackendInterface::CACHE_PERMANENT) {
    // APC serializes/unserializes automatically plus
    // we want to store native types unserialized when possible.
    $this->apcSet($this->getApcuKey($cid), $data, $expire);
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
    apcu_delete($this->getApcuKey($cid));
  }

  /**
   * {@inheritdoc}
   */
  public function deleteMultiple(array $cids) {
    apcu_delete(array_map(array($this, 'getApcuKey'), $cids));
  }

  /**
   * {@inheritdoc}
   */
  public function deleteAll() {
    apcu_delete(new \APCIterator('user', '/^' . preg_quote($this->binPrefix, '/') . '/'));
  }

  /**
   * {@inheritdoc}
   */
  public function garbageCollection() {
    // APC items expire automatically.
  }

  /**
   * {@inheritdoc}
   */
  public function removeBin() {
    apcu_delete(new \APCIterator('user', '/^' . preg_quote($this->binPrefix, '/') . '/'));
  }

  /**
   * {@inheritdoc}
   */
  public function counter($cid, $increment, $default = 0) {
    $success = FALSE;
    $key = $this->getApcuKey($cid);
    apcu_inc($key, $increment, $success);
    if (!$success) {
      if (apcu_exists($key)) {
        throw new \Exception("Counter failed.");
      }
      $this->apcSet($this->getApcuKey($cid), $default, CacheRawBackendInterface::CACHE_PERMANENT);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function counterMultiple(array $cids, $increment, $default = 0) {
    foreach ($cids as $cid) {
      $this->counter($cid, $increment, $default);
    }
  }


  /**
   * {@inheritdoc}
   */
  public function counterSet($cid, $value) {
    $this->set($cid, $value);
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
    if ($result = $this->get($cid)) {
      return (int) $result->data;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function counterGetMultiple(array &$cids) {
    $results = $this->getMultiple($cids);
    $counters = [];
    foreach ($results as $cid => $item) {
      $counters[$cid] = (int) $item->data;
    }
    return $counters;
  }

}
