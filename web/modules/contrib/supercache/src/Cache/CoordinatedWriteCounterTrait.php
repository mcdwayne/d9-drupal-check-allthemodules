<?php

namespace Drupal\supercache\Cache;

/**
 * Used by components to coordinate invalidations
 * between a volatile and a persistent storage.
 *
 * The volatile storage can be a centralized cache
 * such as Couchbase or a decentralized one
 * such as Wincache/Apc.
 *
 * The persistent storage is assumed to always be
 * centralized (such as Database or Couchbase).
 */
trait CoordinatedWriteCounterTrait {

  /**
   * Key used to store the coordinated write counters.
   *
   * @var string
   */
  protected $headKey = "@@coordinated_write_counter_head_id";

  /**
   * Key used to store the coordinated write counters.
   *
   * @var string
   */
  protected $writeKey = "@@coordinated_write_counter_write_count";

  /**
   * Identifier for the instance of the volatile
   * storage.
   *
   * @var string
   */
  protected $headId;

  /**
   * For the persistent backend to be marked
   * as outdated the implementing class
   * must explictly call
   *
   * @var mixed
   */
  protected $doMarkAsOutdatedExplicit = TRUE;

  /**
   * Last timestamp that a local invalidation
   * took place.
   *
   * @var float
   */
  protected $last_invalidation = NULL;

  /**
   * Retrieve the key that identifies the
   * volatile storage head.
   *
   * @return string
   */
  protected function getHeadId() {
    if (!empty($this->headId)) {
      return $this->headId;
    }

    if ($cache = $this->getFastStorage($this->headKey)) {
      $this->headId = $cache->data;
    }
    else {
      // Generate a unique ID for this fast backend.
      $this->headId = uniqid(microtime(TRUE), TRUE);
      $this->setFastStorage($this->headKey, $this->headId);
    }

    return $this->headId;
  }

  /**
   * The time at which the last write to this cache bin happened.
   *
   * @var float
   */
  protected $lastWrite = NULL;

  /**
   * Summary of $fastStorageInvalid
   * @var mixed
   */
  protected $fastStorageInvalid;

  /**
   * Some storage backends cannot rely
   * on the information provided by
   * $this->getLastWrite() to tell what items
   * they should invalidate. Calling this method
   * will clear all of the fast backend
   * if it is considered not to be consistent
   * with the contents of the persistent backend.
   * 
   * Will only work once per instance.
   * 
   */
  protected function clearFastStorageIfInvalid() {
    if (empty($this->fastStorageInvalid)) {
      $this->getLastWrite();
      if ($this->fastStorageInvalid) {
        $this->clearFastStorage();
      }
    }
  }

  /**
   * Items retrieve from the persistent
   * backend that have been modified prior
   * to this timestamp are to be considered
   * outdated.
   */
  protected function getLastWrite() {
    if ($this->lastWrite === NULL) {
      $cache = $this->getPersistentStorage($this->writeKey);
      if ($cache && $cache->data['head'] != $this->getHeadId()) {
        // Someone that was not us did the last write, so take
        // their timestamp.
        $this->lastWrite = $cache->data['timestamp'];
        if ($cache = $this->getFastStorage($this->writeKey)) {
          if ($this->lastWrite != $cache->data) {
            $this->setFastStorage($this->writeKey, $this->lastWrite);
            $this->fastStorageInvalid = TRUE;
          }
        }
      }
      else {
        // If we are here this means that either the binary has never been invalidated,
        // or that the last invalidation was actually made by ourselves so we retain
        // the previous invalidation timestamp that we had.
        $current = $cache && $cache->data['head'] == $this->getHeadId() ? $cache->data['timestamp'] : 0;
        $cache = $this->getFastStorage($this->writeKey);
        $this->lastWrite = $cache && !empty($cache->data) ? $cache->data : $current;
        $this->fastStorageInvalid = FALSE;
      }
    }
    return $this->lastWrite;
  }

  /**
   * Notify that a write has happened, it does
   * not inmediately invalidate the persistent
   * storage.
   */
  protected function markAsOutdated() {
    // Clocks on a single server can drift. Multiple servers may have slightly
    // differing opinions about the current time. Given that, do not assume
    // 'now' on this server is always later than our stored timestamp.
    // Also add 1 millisecond, to ensure that caches written earlier in the same
    // millisecond are invalidated. It is possible that caches will be later in
    // the same millisecond and are then incorrectly invalidated, but that only
    // costs one additional roundtrip to the persistent cache.
    $now = round(microtime(TRUE) + .001, 3);
    if ($now > $this->getLastWrite()) {
      if ($this->doMarkAsOutdatedExplicit) {
        // Invalidate when the object is destroyed.
        $this->last_invalidation = $now;
      }
      else {
        $this->_doMarkAsOutdated($now);
      }
    }
  }

  /**
   * To be called by the implementing class whenever
   * it wants to persistent the last invalidation.
   */
  protected function doMarkAsOutdatedExplicitCall() {
    if ($this->doMarkAsOutdatedExplicit && !empty($this->last_invalidation)) {
      $this->_doMarkAsOutdated($this->last_invalidation);
    }
  }

  /**
   * Mark as outdated.
   */
  protected function _doMarkAsOutdated($lastWriteLocal) {
    $this->setFastStorage($this->writeKey, $this->lastWrite);
    $this->setPersistentStorage($this->writeKey, ['head' => $this->getHeadId(), 'timestamp' => $lastWriteLocal]);
  }

  /**
   * @return mixed
   */
  public function getFastStorage($cid) {
    throw new \Exception("Not implemented.");
  }

  /**
   * @return mixed
   */
  public function getPersistentStorage($cid) {
    throw new \Exception("Not implemented.");
  }

  /**
   * @return mixed
   */
  public function setFastStorage($cid, $value) {
    throw new \Exception("Not implemented.");
  }

  /**
   * @return mixed
   */
  public function setPersistentStorage($cid, $value) {
    throw new \Exception("Not implemented.");
  }

  public function clearFastStorage() {
    throw new \Exception("Not implemented.");
  }
}
