<?php

/**
 * @file
 * Contains \Drupal\cachemg\Database\CachemgDatabaseBackend.
 */

namespace Drupal\cachemg\Database;

use Drupal\Core\Database\Connection;
use Drupal\Core\Cache\DatabaseBackend;

/**
 * Defines a wrapper around default database cache implementation.
 *
 * @ingroup cache
 */
class CachemgDatabaseBackend extends DatabaseBackend {

  /**
   * List of cache objects that loads during class initialization.
   * Later all cache get requests will use this in-memory object
   * instead of direct database query.
   *
   * @var array
   */
  protected $preloaded_cache = array();

  /**
   * Constructs a DatabaseBackend object.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param string $bin
   *   The cache bin for which the object is created.
   * @param $cachemg_cids
   *   List of cache ids for the current cache bin that
   *   have to be preloaded with one multiget query.
   */
  public function __construct(Connection $connection, $bin, $cachemg_cids) {
    parent::__construct($connection, $bin);

    // If we already have list of used cids for the current page,
    // then we can preload all of them with a multiple query and keep them
    // in memory. Then, on any cache get requert, we will just return cached
    // value from static cache instead of making a new database query.
    if (!empty($cachemg_cids)) {
      $this->preloaded_cache = parent::getMultiple($cachemg_cids);
    }
  }

  /**
   * Implements Drupal\Core\Cache\CacheBackendInterface::getMultiple().
   */
  public function getMultiple(&$cids, $allow_invalid = FALSE) {

    // Save all cache id that were requested for the current page.
    $requested_cids = &drupal_static('cachemg:requested_cids', array());

    // Make sure that list of cache ids for the current cache bin
    // at least initialized as an empty array.
    $requested_cids[$this->bin] = isset($requested_cids[$this->bin]) ? $requested_cids[$this->bin] : array();

    // Merge array with already logged cache ids and array with currently
    // requested cache ids. Later we will use this merged array to store a list
    // of cache bins and cache cids that were requested on that page.
    $requested_cids[$this->bin] = array_merge($requested_cids[$this->bin], $cids);

    // Searching for the requested cache ids in the preloaded cache array,
    // that stores in memory. In a perfect case all cids should be found here.
    $preloaded_cache = array();
    foreach ($cids as $cid) {
      if (!empty($this->preloaded_cache[$cid])) {
        $preloaded_cache[$cid] = $this->preloaded_cache[$cid];
      }
    }

    // Get info about cache ids that were not found in preloaded cache.
    $cids = array_diff($cids, array_keys($preloaded_cache));

    // If we still have cache ids that were not foundn in preloaded cache,
    // then we have to request that cache using default Drupal database cache
    // backend. In a perfect case this should happen only during first page
    // load, when there are no cache to preload.
    if (!empty($cids)) {
      $cache = parent::getMultiple($cids, $allow_invalid);
    }

    // If we got any cache from the database, then return merged data from
    // preloaded cache and database cache. Otherwise we have to return only
    // preloaded cache.
    return !empty($cache) ? array_merge($preloaded_cache, $cache) : $preloaded_cache;
  }
}
