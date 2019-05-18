<?php

/**
 * @file
 * Contains \Drupal\supercache\Cache\ChainedFastBackend.
 */

namespace Drupal\supercache\Cache;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Site\Settings;

/**
 * Defines a backend with a fast and a consistent backend chain.
 *
 * In order to mitigate a network roundtrip for each cache get operation, this
 * cache allows a fast backend to be put in front of a slow(er) backend.
 * Typically the fast backend will be something like APCu, and be bound to a
 * single web node, and will not require a network round trip to fetch a cache
 * item. The fast backend will also typically be inconsistent (will only see
 * changes from one web node). The slower backend will be something like Mysql,
 * Memcached or Redis, and will be used by all web nodes, thus making it
 * consistent, but also require a network round trip for each cache get.
 *
 * In addition to being useful for sites running on multiple web nodes, this
 * backend can also be useful for sites running on a single web node where the
 * fast backend (For example, APCu) isn't shareable between the web and CLI processes.
 * Single-node configurations that don't have that limitation can just use the
 * fast cache backend directly.
 *
 * We always use the fast backend when reading (get()) entries from cache, but
 * check whether they were created before the last write (set()) to this
 * (chained) cache backend. Those cache entries that were created before the
 * last write are discarded, but we use their cache IDs to then read them from
 * the consistent (slower) cache backend instead; at the same time we update
 * the fast cache backend so that the next read will hit the faster backend
 * again. Hence we can guarantee that the cache entries we return are all
 * up-to-date, and maximally exploit the faster cache backend. This cache
 * backend uses and maintains a "last write timestamp" to determine which cache
 * entries should be discarded.
 *
 * Because this backend will mark all the cache entries in a bin as out-dated
 * for each write to a bin, it is best suited to bins with fewer changes.
 *
 * Note that this is designed specifically for combining a fast inconsistent
 * cache backend with a slower consistent cache back-end. To still function
 * correctly, it needs to do a consistency check (see the "last write timestamp"
 * logic). This contrasts with \Drupal\Core\Cache\BackendChain, which assumes
 * both chained cache backends are consistent, thus a consistency check being
 * pointless.
 *
 * @see \Drupal\Core\Cache\BackendChain
 *
 * @ingroup cache
 */
class ChainedFastBackend implements CacheBackendInterface {

  use CoordinatedWriteCounterTrait;

  /**
   * @var string
   */
  protected $bin;

  /**
   * The consistent cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $consistentBackend;

  /**
   * The fast cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $fastBackend;

  /**
   * Constructs a ChainedFastBackend object.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $consistent_backend
   *   The consistent cache backend.
   * @param \Drupal\Core\Cache\CacheBackendInterface $fast_backend
   *   The fast cache backend.
   * @param string $bin
   *   The cache bin for which the object is created.
   * @throws \Exception
   *   When the consistent cache backend and the fast cache backend are the same
   *   service.
   */
  public function __construct(CacheBackendInterface $consistent_backend, CacheBackendInterface $fast_backend, $bin, $mark_as_outdated_explicit = FALSE) {
    if ($consistent_backend == $fast_backend) {
      // @todo: should throw a proper exception. See https://www.drupal.org/node/2751847.
      trigger_error('Consistent cache backend and fast cache backend cannot use the same service.', E_USER_ERROR);
    }
    $this->consistentBackend = $consistent_backend;
    $this->fastBackend = $fast_backend;
    $this->bin = 'cache_' . $bin;
    // We explicitly execute the outdated call on shutdown.
    $this->doMarkAsOutdatedExplicit = $mark_as_outdated_explicit;
  }

  /**
   * {@inheritdoc}
   */
  public function get($cid, $allow_invalid = FALSE) {
    $cids = array($cid);
    $cache = $this->getMultiple($cids, $allow_invalid);
    return reset($cache);
  }

  /**
   * {@inheritdoc}
   */
  public function getMultiple(&$cids, $allow_invalid = FALSE) {
    $cids_copy = $cids;
    $cache = array();

    // If we can determine the time at which the last write to the consistent
    // backend occurred (we might not be able to if it has been recently
    // flushed/restarted), then we can use that to validate items from the fast
    // backend, so try to get those first. Otherwise, we can't assume that
    // anything in the fast backend is valid, so don't even bother fetching
    // from there.
    $last_write_timestamp = $this->getLastWrite();
    if ($last_write_timestamp) {
      // Items in the fast backend might be invalid based on their timestamp,
      // but we can't check the timestamp prior to getting the item, which
      // includes unserializing it. However, unserializing an invalid item can
      // throw an exception. For example, a __wakeup() implementation that
      // receives object properties containing references to code or data that
      // no longer exists in the application's current state.
      //
      // Unserializing invalid data, whether it throws an exception or not, is
      // a waste of time, but we only incur it while a cache invalidation has
      // not yet finished propagating to all the fast backend instances.
      //
      // Most cache backend implementations should not wrap their internal
      // get() implementations with a try/catch, because they have no reason to
      // assume that their data is invalid, and doing so would mask
      // unserialization errors of valid data. We do so here, only because the
      // fast backend is non-authoritative, and after discarding its
      // exceptions, we proceed to check the consistent (authoritative) backend
      // and allow exceptions from that to bubble up.
      try {
        $items = $this->fastBackend->getMultiple($cids, $allow_invalid);
      }
      catch (\Exception $e) {
        $cids = $cids_copy;
        $items = array();
      }

      // Even if items were successfully fetched from the fast backend, they
      // are potentially invalid if older than the last time the bin was
      // written to in the consistent backend, so only keep ones that aren't.
      foreach ($items as $item) {
        if ($item->created < $last_write_timestamp) {
          $cids[array_search($item->cid, $cids_copy)] = $item->cid;
        }
        else {
          $cache[$item->cid] = $item;
        }
      }
    }

    // If there were any cache entries that were not available in the fast
    // backend, retrieve them from the consistent backend and store them in the
    // fast one.
    if ($cids) {
      $missing = array();
      foreach ($this->consistentBackend->getMultiple($cids, $allow_invalid) as $item) {
        $cache[$item->cid] = $item;
        $missing[$item->cid] = (array) $item;
      }
      if (!empty($missing)) {
        // TODO: Expiration data from the consistent
        // backend is lost here. We are setting items
        // in the fast backend with permanent status...
        // But time based expirations are becoming less
        // relevant, and considering that the items
        // in the consistent backend will actually expire
        // properly, this might not be that of an issue.
        $this->fastBackend->setMultiple($missing);
      }
      // This means that the persistent backend has no lastWriteTimestamp
      // but we were able to retrieve data from it....
      if (!$last_write_timestamp && !empty($missing)) {
        $this->markAsOutdated();
      }
    }

    return $cache;
  }

  /**
   * {@inheritdoc}
   */
  public function set($cid, $data, $expire = Cache::PERMANENT, array $tags = array()) {
    $this->consistentBackend->set($cid, $data, $expire, $tags);
    $this->fastBackend->set($cid, $data, $expire, $tags);
    $this->markAsOutdated();
  }

  /**
   * {@inheritdoc}
   */
  public function setMultiple(array $items) {
    if (empty($items)) {
      return;
    }
    $this->consistentBackend->setMultiple($items);
    $this->fastBackend->setMultiple($items);
    $this->markAsOutdated();
  }

  /**
   * {@inheritdoc}
   */
  public function delete($cid) {
    $this->consistentBackend->deleteMultiple(array($cid));
    $this->fastBackend->deleteMultiple(array($cid));
    $this->markAsOutdated();
  }

  /**
   * {@inheritdoc}
   */
  public function deleteMultiple(array $cids) {
    $this->consistentBackend->deleteMultiple($cids);
    $this->fastBackend->deleteMultiple($cids);
    $this->markAsOutdated();
  }

  /**
   * {@inheritdoc}
   */
  public function deleteAll() {
    $this->consistentBackend->deleteAll();
    $this->fastBackend->deleteAll();
    $this->markAsOutdated();
  }

  /**
   * {@inheritdoc}
   */
  public function garbageCollection() {
    $this->consistentBackend->garbageCollection();
    $this->fastBackend->garbageCollection();
  }

  /**
   * {@inheritdoc}
   */
  public function removeBin() {
    $this->consistentBackend->removeBin();
    $this->fastBackend->removeBin();
  }

  /**
   * @todo Document in https://www.drupal.org/node/2311945.
   */
  public function reset() {
    $this->lastWrite = NULL;
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
    $this->consistentBackend->invalidateMultiple($cids);
    $this->fastBackend->invalidateMultiple($cids);
    $this->markAsOutdated();
  }

  /**
   * {@inheritdoc}
   */
  public function invalidateAll() {
    $this->consistentBackend->invalidateAll();
    $this->fastBackend->invalidateAll();
    $this->markAsOutdated();
  }

  /**
   * Shutdown functions.
   *
   * Using __destruct() proved to be problematic
   * with some some cache backends such as couchbase
   * with custom transcoders or the Drupal.org
   * test bot.
   *
   * But because binaries are not services... we rely
   * on the ChainedFastBackend factory to subscribe to
   * the onKernelTerminate event and call us.
   *
   */
  public function onKernelTerminate() {
    $this->doMarkAsOutdatedExplicitCall();
    // Once this is done here, any further invalidations
    // must be done as they come.
    $this->doMarkAsOutdatedExplicit = FALSE;
  }

  #region Helper implementations for CoordinatedWriteCounterTrait

  /**
   * {@inheritdoc}
   */
  public function getFastStorage($cid) {
    return $this->fastBackend->get($cid);
  }

  /**
   * {@inheritdoc}
   */
  public function getPersistentStorage($cid) {
    return $this->consistentBackend->get($cid);
  }

  /**
   * {@inheritdoc}
   */
  public function setFastStorage($cid, $value) {
    $this->fastBackend->set($cid, $value);
  }

  /**
   * {@inheritdoc}
   */
  public function setPersistentStorage($cid, $value) {
    $this->consistentBackend->set($cid, $value);
  }

  /**
   * {@inheritdoc}
   */
  public function clearFastStorage($cid, $value) {
    $this->fastBackend->deleteAll();
  }

  #endregion

}
