<?php

/**
 * @file
 * Contains \Drupal\supercache\Cache\ChainedFastRawBackend.
 */

namespace Drupal\supercache\Cache;

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
class ChainedFastRawBackend implements CacheRawBackendInterface {

  use RequestTimeTrait;
  use CoordinatedWriteCounterTrait;

  /**
   * Override this method from RequestTimeTrait.
   */
  public function refreshRequestTime() {
    $this->fastBackend->refreshRequestTime();
    $this->consistentBackend->refreshRequestTime();
  }

  /**
   * @var string
   */
  protected $bin;

  /**
   * Name of the bin assigned to the fastBackend.
   *
   * @var string
   */
  protected $fastBin;

  /**
   * The consistent cache backend.
   *
   * @var CacheRawBackendInterface
   */
  protected $consistentBackend;

  /**
   * The fast cache backend.
   *
   * @var CacheRawBackendInterface
   */
  protected $fastBackend;

  /**
   * Constructs a ChainedFastBackend object.
   *
   * @param CacheRawBackendInterface $consistent_backend
   *   The consistent cache backend.
   * @param CacheRawBackendInterface $fast_backend
   *   The fast cache backend.
   * @param CacheRawBackendInterface $fast_backend_invalidations
   *   The fast cache backend used to store invalidations. Must always have the same binary.
   * @throws \Exception
   *   When the consistent cache backend and the fast cache backend are the same
   *   service.
   */
  public function __construct(CacheRawBackendInterface $consistent_backend, CacheRawBackendInterface $fast_backend, $mark_as_outdated_explicit = FALSE) {
    if ($consistent_backend == $fast_backend) {
      // @todo: should throw a proper exception. See https://www.drupal.org/node/2751847.
      trigger_error('Consistent cache backend and fast cache backend cannot use the same service.', E_USER_ERROR);
    }
    $this->consistentBackend = $consistent_backend;
    $this->fastBackend = $fast_backend;
    $this->doMarkAsOutdatedExplicit = $mark_as_outdated_explicit;
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

    $this->clearFastStorageIfInvalid();

    $cids_copy = $cids;
    $cache = array();

    try {
      $items = $this->fastBackend->getMultiple($cids);
    }
    catch (\Exception $e) {
      $cids = $cids_copy;
      $items = array();
    }

    // Even if items were successfully fetched from the fast backend, they
    // are potentially invalid if older than the last time the bin was
    // written to in the consistent backend, so only keep ones that aren't.
    $cache = $items;

    // If there were any cache entries that were not available in the fast
    // backend, retrieve them from the consistent backend and store them in the
    // fast one.
    if ($cids) {
      $missing = array();
      $cached = $this->consistentBackend->getMultiple($cids);
      foreach ($cached as $cid => $item) {
        $cache[$cid] = $item;
        $missing[$cid] = ['data' => $item->data];
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
    }

    return $cache;
  }

  /**
   * {@inheritdoc}
   */
  public function set($cid, $data, $expire = Cache::PERMANENT) {
    $this->consistentBackend->set($cid, $data, $expire);
    $this->fastBackend->set($cid, $data, $expire);
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
    $this->lastWriteTimestamp = NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function counter($cid, $increment, $default = 0) {
    $this->consistentBackend->counter($cid, $increment, $default);
    $this->fastBackend->counterSet($cid, $increment, $default);
    $this->markAsOutdated();
  }

  /**
   * {@inheritdoc}
   */
  public function counterMultiple(array $cids, $increment, $default = 0) {
    $this->consistentBackend->counterMultiple($cids, $increment, $default);
    $this->fastBackend->counterMultiple($cids, $increment, $default);
    $this->markAsOutdated();
  }


  /**
   * {@inheritdoc}
   */
  public function counterSet($cid, $value) {
    $this->consistentBackend->set($cid, $value);
    $this->fastBackend->set($cid, $value);
    $this->markAsOutdated();
  }

  /**
   * {@inheritdoc}
   */
  public function counterSetMultiple(array $items) {
    foreach ($items as $cid => $item) {
      $this->consistentBackend->set($cid, (int) $item);
      $this->fastBackend->set($cid, (int) $item);
    }
    $this->markAsOutdated();
  }

  /**
   * {@inheritdoc}
   */
  public function counterGet($cid) {
    $result = $this->counterGetMultiple[$cid];
    return reset($result);
  }

  /**
   * {@inheritdoc}
   */
  public function counterGetMultiple(array &$cids) {

    $this->clearFastStorageIfInvalid();

    $cids_copy = $cids;
    $cache = array();

    try {
      $items = $this->fastBackend->counterGetMultiple($cids);
    }
    catch (\Exception $e) {
      $cids = $cids_copy;
      $items = array();
    }

    // Even if items were successfully fetched from the fast backend, they
    // are potentially invalid if older than the last time the bin was
    // written to in the consistent backend, so only keep ones that aren't.
    $cache = $items;

    // If there were any cache entries that were not available in the fast
    // backend, retrieve them from the consistent backend and store them in the
    // fast one.
    if ($cids) {
      $missing = array();
      $cached = $this->consistentBackend->counterGetMultiple($cids);
      foreach ($cached as $cid => $item) {
        $cache[$cid] = $item;
        $missing[$cid] = $item;
      }
      if (!empty($missing)) {
        // TODO: Expiration data from the consistent
        // backend is lost here. We are setting items
        // in the fast backend with permanent status...
        // But time based expirations are becoming less
        // relevant, and considering that the items
        // in the consistent backend will actually expire
        // properly, this might not be that of an issue.
        // A possibility is not to support expiration
        // for the ChainedFastRawBackend...
        $this->fastBackend->counterSetMultiple($missing);
      }
    }

    return $cache;
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
