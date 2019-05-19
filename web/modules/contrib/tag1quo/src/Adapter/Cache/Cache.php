<?php

namespace Drupal\tag1quo\Adapter\Cache;

use Drupal\tag1quo\VersionedClass;

/**
 * Class Cache.
 *
 * @internal This class is subject to change.
 */
class Cache extends VersionedClass {

  const PERMANENT = 0;

  protected $bin;

  protected $defaultBin = 'cache';

  /**
   * Cache constructor.
   *
   * @param string $bin
   *   The cache bin to use. If not provided, the default bin for the
   *   version of core will be used.
   */
  public function __construct($bin = NULL) {
    if (empty($bin)) {
      $bin = $this->defaultBin;
    }
    $this->bin = $bin;
  }

  /**
   * Retrieves a cache bin.
   *
   * @param string $bin
   *   The cache bin to use. If not provided, the default bin for the
   *   version of core will be used.
   *
   * @return static
   */
  public static function load($bin = NULL) {
    return static::createVersionedStaticInstance(array($bin));
  }

  /**
   * Returns data from the persistent cache.
   *
   * @param string $cid
   *   The cache ID of the data to retrieve.
   *
   * @return object|false
   *   The cache item or FALSE on failure.
   */
  public function get($cid) {
    return \cache_get($cid, $this->bin);
  }

  /**
   * Stores data in the persistent cache.
   *
   * @param string $cid
   *   The cache ID of the data to store.
   * @param mixed $data
   *   The data to store in the cache.
   *   Some storage engines only allow objects up to a maximum of 1MB in size to
   *   be stored by default. When caching large arrays or similar, take care to
   *   ensure $data does not exceed this size.
   * @param int $expire
   *   One of the following values:
   *   - \Drupal\tag1quo\Adapter\Cache\Cache::PERMANENT: Indicates that the
   *     item should not be removed unless it is deleted explicitly.
   *   - A Unix timestamp: Indicates that the item will be considered invalid
   *     after this time, i.e. it will not be returned by get() unless
   *     $allow_invalid has been set to TRUE. When the item has expired, it may
   *     be permanently deleted by the garbage collector at any time.
   *
   * @return static
   */
  public function set($cid, $data, $expire = self::PERMANENT) {
    \cache_set($cid, $data, $this->bin, $expire);
    return $this;
  }



}
