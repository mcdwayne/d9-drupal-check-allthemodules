<?php

/**
 * @file
 * Contains \Drupal\supercache\Cache\CacheRawBackendInterface.
 */

namespace Drupal\supercache\Cache;

/**
 * Defines an interface for cache implementations.
 *
 * A simple cache backend implementation that exposes
 * the underlying storage in the most simple way possible.
 *
 * This class was initialy implemented to serve as a storage
 * backend for the CacheCacheTagsChecksum class.
 *
 * @ingroup cache
 */
interface CacheRawBackendInterface {

  /**
   * Indicates that the item should never be removed unless explicitly deleted.
   */
  const CACHE_PERMANENT = -1;

  /**
   * Returns data from the persistent cache.
   *
   * @param string $cid
   *   The cache ID of the data to retrieve.
   *
   * @return object|false
   *   The cache item or FALSE on failure.
   *
   * @see \Drupal\Core\Cache\CacheRawBackendInterface::getMultiple()
   */
  public function get($cid);

  /**
   * Returns data from the persistent cache when given an array of cache IDs.
   *
   * @param array $cids
   *   An array of cache IDs for the data to retrieve. This is passed by
   *   reference, and will have the IDs successfully returned from cache
   *   removed.
   *
   * @return array
   *   An array of cache item objects indexed by cache ID.
   *
   * @see \Drupal\Core\Cache\CacheRawBackendInterface::get()
   */
  public function getMultiple(&$cids);

  /**
   * Stores data in the persistent cache.
   *
   * Core cache implementations set the created time on cache item with
   * microtime(TRUE) rather than REQUEST_TIME_FLOAT, because the created time
   * of cache items should match when they are created, not when the request
   * started. Apart from being more accurate, this increases the chance an
   * item will legitimately be considered valid.
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
   *   - CacheBackendInterface::CACHE_PERMANENT: Indicates that the item should
   *     not be removed unless it is deleted explicitly.
   *   - A Unix timestamp: Indicates that the item will be considered invalid
   *     after this time, that is, it will not be returned by get() unless
   *     $allow_invalid has been set to TRUE. When the item has expired, it may
   *     be permanently deleted by the garbage collector at any time.
   *
   * @see \Drupal\Core\Cache\CacheRawBackendInterface::get()
   * @see \Drupal\Core\Cache\CacheRawBackendInterface::getMultiple()
   */
  public function set($cid, $data, $expire = CacheRawBackendInterface::CACHE_PERMANENT);

  /**
   * Store multiple items in the persistent cache.
   *
   * @param array $items
   *   An array of cache items, keyed by cid. In the form:
   *   @code
   *   $items = array(
   *     $cid => array(
   *       // Required.
   *       'data' => $data,
   *       // Optional, defaults to CacheRawBackendInterface::CACHE_PERMANENT.
   *       'expire' => CacheRawBackendInterface::CACHE_PERMANENT,
   *     ),
   *   );
   *   @endcode
   */
  public function setMultiple(array $items);

  /**
   * Deletes an item from the cache.
   *
   * If the cache item is being deleted because it is no longer "fresh", you may
   * consider using invalidate() instead. This allows callers to retrieve the
   * invalid item by calling get() with $allow_invalid set to TRUE. In some cases
   * an invalid item may be acceptable rather than having to rebuild the cache.
   *
   * @param string $cid
   *   The cache ID to delete.
   *
   * @see \Drupal\Core\Cache\CacheRawBackendInterface::deleteMultiple()
   * @see \Drupal\Core\Cache\CacheRawBackendInterface::deleteAll()
   */
  public function delete($cid);

  /**
   * Deletes multiple items from the cache.
   *
   * If the cache items are being deleted because they are no longer "fresh",
   * you may consider using invalidateMultiple() instead. This allows callers to
   * retrieve the invalid items by calling get() with $allow_invalid set to TRUE.
   * In some cases an invalid item may be acceptable rather than having to
   * rebuild the cache.
   *
   * @param array $cids
   *   An array of cache IDs to delete.
   *
   * @see \Drupal\supercache\Cache\CacheRawBackendInterface::delete()
   * @see \Drupal\supercache\Cache\CacheRawBackendInterface::deleteAll()
   */
  public function deleteMultiple(array $cids);

  /**
   * Deletes all cache items in a bin.
   *
   * @see \Drupal\supercache\Cache\CacheRawBackendInterface::delete()
   * @see \Drupal\supercache\Cache\CacheRawBackendInterface::deleteMultiple()
   */
  public function deleteAll();

  /**
   * Add an increment (can be negative) to the stored cache data. Only
   * works for stored numeric data.
   *
   * @param string $cide
   *   The cache ID or array of ID's.
   * @param int $increment
   *   The amount to increment or decrement.
   * @param int $default
   *   Default value.
   */
  public function counter($cid, $increment, $default = 0);

  /**
   * Add an increment (can be negative) to the stored cache data. Only
   * works for stored numeric data.
   *
   * @param string[] $cide
   *   The cache ID or array of ID's.
   * @param int $increment
   *   The amount to increment or decrement.
   * @param int $default
   *   Default value.
   */
  public function counterMultiple(array $cids, $increment, $default = 0);

  /**
   * Set the value for a counter storage item.
   *
   * On some backends you can actually simply call set()
   * but others require to provide, for example, an ASCII
   * representation of the value.
   *
   * @param string $cid
   *   The cache id.
   *
   * @param int $value
   *   The value.
   */
  public function counterSet($cid, $value);

  /**
   * Set the value of counter variables in batch.
   *
   * @param array $items
   *   An array of counter values, keyed by cid. In the form:
   *   @code
   *   $items = array(
   *     $cid => $value
   *   );
   *   @endcode
   */
  public function counterSetMultiple(array $items);

  /**
   * Get the value of a counter variable.
   *
   * Some backends do not store counters as
   * numeric data. So if you call get($cid) you
   * might obtain unexpected results.
   *
   * @param string $cid
   *   The cache id.
   */
  public function counterGet($cid);

  /**
   * Get multiple counter values at once.
   *
   * @see self::getCounter()
   *
   * @param array $cids
   *   An array of cache id's to retrieve.
   */
  public function counterGetMultiple(array &$cids);

  /**
   * Performs garbage collection on a cache bin.
   *
   * The backend may choose to delete expired or invalidated items.
   */
  public function garbageCollection();

  /**
   * Remove a cache bin.
   */
  public function removeBin();

  /**
   * Make sure that the time used for
   * expirations gets refreshed. The main
   * purpose of this is testing.
   */
  public function refreshRequestTime();
}
