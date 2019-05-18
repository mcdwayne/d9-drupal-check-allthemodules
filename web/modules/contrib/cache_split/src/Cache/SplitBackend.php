<?php
/**
 * @file
 * Contains \Drupal\cache_split\Cache\SplitBackend.
 */

namespace Drupal\cache_split\Cache;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;

/**
 * Cache split backend.
 */
class SplitBackend implements CacheBackendInterface {

  /**
   * @var \Drupal\cache_split\Cache\CacheBackendMatcherCollection
   */
  protected $collection;

  /**
   * SplitBackend constructor.
   *
   * @param \Drupal\cache_split\Cache\CacheBackendMatcherCollection $collection
   */
  public function __construct(CacheBackendMatcherCollection $collection) {
    $this->collection = $collection;
  }

  /**
   * {@inheritdoc}
   */
  public function get($cid, $allow_invalid = FALSE) {
    return $this->collection->callSingle($cid, __FUNCTION__, [$allow_invalid]);
  }

  /**
   * {@inheritdoc}
   */
  public function getMultiple(&$cids, $allow_invalid = FALSE) {
    return $this->collection->callMultipleByRef($cids, __FUNCTION__, [$allow_invalid]);
  }

  /**
   * {@inheritdoc}
   */
  public function set($cid, $data, $expire = Cache::PERMANENT, array $tags = array()) {
    return $this->collection->callSingle($cid, __FUNCTION__, [$data, $expire, $tags]);
  }

  /**
   * {@inheritdoc}
   */
  public function setMultiple(array $items) {
    return $this->collection->callMultipleByKey($items, __FUNCTION__);
  }

  /**
   * {@inheritdoc}
   */
  public function delete($cid) {
    return $this->collection->callSingle($cid, __FUNCTION__);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteMultiple(array $cids) {
    return $this->collection->callMultiple($cids, __FUNCTION__);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteAll() {
    return $this->collection->callAll(__FUNCTION__);
  }

  /**
   * {@inheritdoc}
   */
  public function invalidate($cid) {
    return $this->collection->callSingle($cid, __FUNCTION__);
  }

  /**
   * {@inheritdoc}
   */
  public function invalidateMultiple(array $cids) {
    return $this->collection->callMultiple($cids, __FUNCTION__);
  }

  /**
   * {@inheritdoc}
   */
  public function invalidateAll() {
    return $this->collection->callAll(__FUNCTION__);
  }

  /**
   * {@inheritdoc}
   */
  public function garbageCollection() {
    return $this->collection->callAll(__FUNCTION__);
  }

  /**
   * {@inheritdoc}
   */
  public function removeBin() {
    return $this->collection->callAll(__FUNCTION__);
  }

}
