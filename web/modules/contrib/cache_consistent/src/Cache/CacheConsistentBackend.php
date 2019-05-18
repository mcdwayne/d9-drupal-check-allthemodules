<?php

namespace Drupal\cache_consistent\Cache;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\CacheTagsChecksumInterface;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;

/**
 * Class CacheConsistent.
 *
 * @package Drupal\cache_consistent\Cache
 *
 * @ingroup cache_consistent
 */
class CacheConsistentBackend implements CacheBackendInterface, CacheTagsInvalidatorInterface {

  use CacheBufferAwareTrait;
  use CacheTagsChecksumAwareTrait;

  /**
   * Disable transactional cache set's.
   *
   * @var bool
   */
  protected $disableTransactionalCacheSet = FALSE;

  /**
   * CacheConsistent constructor.
   *
   * @param \Drupal\cache_consistent\Cache\CacheConsistentBufferInterface $cache_buffer
   *   The buffered cache backend object.
   * @param \Drupal\Core\Cache\CacheTagsChecksumInterface $checksum_provider
   *   The buffered checksum provider.
   * @param int $isolation_level
   *   The isolation level.
   *
   * @codeCoverageIgnore
   *   Too difficult to test constructors.
   */
  public function __construct(CacheConsistentBufferInterface $cache_buffer, CacheTagsChecksumInterface $checksum_provider, $isolation_level = CacheConsistentIsolationLevel::DEFAULT_LEVEL) {
    $this->setCacheBuffer($cache_buffer);
    $this->setChecksumProvider($checksum_provider);

    // If isolation level is repeatable-read or stronger, then disable cache set
    // during transactions.
    $this->disableTransactionalCacheSet = $isolation_level >= CacheConsistentIsolationLevel::REPEATABLE_READ;
  }

  /**
   * Prepare a cache item.
   *
   * @param mixed $item
   *   The cache item.
   * @param bool $allow_invalid
   *   Allow invalid items.
   *
   * @return mixed|bool
   *   Item if valid, FALSE if not.
   */
  protected function prepareItem($item, $allow_invalid = FALSE) {
    if (!$item) {
      return FALSE;
    }

    // If item is not buffered, then use a zero checksum against the consistent
    // checksum provider.
    if (empty($item->buffered)) {
      $item->checksum = 0;
    }

    $item->valid = $item->valid && ($this->checksumProvider && $this->checksumProvider->isValid($item->checksum, $item->tags));

    return $item->valid || $allow_invalid ? $item : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function get($cid, $allow_invalid = FALSE) {
    $cids = [$cid];
    $items = $this->getMultiple($cids, $allow_invalid);
    $item = reset($items);
    return $item ? $item : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getMultiple(&$cids, $allow_invalid = FALSE) {
    $original_cids = $cids;

    // Fetch cache items from buffer and backend.
    // item = FALSE : Invalid or deleted.
    $buffered_items = $this->cacheBuffer->getMultiple($cids, $allow_invalid);
    $fetched_items = $cids ? $this->cacheBuffer->getCacheBackend()->getMultiple($cids, $allow_invalid) : [];

    // Merge cache items from buffer and backend.
    $items = $buffered_items + $fetched_items;

    // Remove cache items from the backend, if they are invalidated by the
    // buffer.
    if ($items) {
      // Remove invalid items in the buffer.
      foreach ($items as $cid => $item) {
        $item = $this->prepareItem($item, $allow_invalid);
        if (!$item) {
          unset($items[$cid]);
        }
      }
    }

    $cids = array_diff($original_cids, array_keys($items));
    return $items;
  }

  /**
   * {@inheritdoc}
   */
  public function set($cid, $data, $expire = Cache::PERMANENT, array $tags = array()) {
    if ($this->disableTransactionalCacheSet && $this->cacheBuffer->transactionDepth() > 0) {
      return $this->delete($cid);
    }
    return $this->cacheBuffer->set($cid, $data, $expire, $tags);
  }

  /**
   * {@inheritdoc}
   */
  public function setMultiple(array $items) {
    if ($this->disableTransactionalCacheSet && $this->cacheBuffer->transactionDepth() > 0) {
      return $this->deleteMultiple(array_keys($items));
    }
    return $this->cacheBuffer->setMultiple($items);
  }

  /**
   * {@inheritdoc}
   */
  public function delete($cid) {
    return $this->cacheBuffer->delete($cid);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteMultiple(array $cids) {
    return $this->cacheBuffer->deleteMultiple($cids);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteAll() {
    return $this->cacheBuffer->deleteAll();
  }

  /**
   * {@inheritdoc}
   */
  public function invalidate($cid) {
    return $this->cacheBuffer->invalidate($cid);
  }

  /**
   * {@inheritdoc}
   */
  public function invalidateMultiple(array $cids) {
    return $this->cacheBuffer->invalidateMultiple($cids);
  }

  /**
   * {@inheritdoc}
   */
  public function invalidateAll() {
    return $this->cacheBuffer->invalidateAll();
  }

  /**
   * {@inheritdoc}
   */
  public function garbageCollection() {
    return $this->cacheBuffer->garbageCollection();
  }

  /**
   * {@inheritdoc}
   */
  public function removeBin() {
    return $this->cacheBuffer->removeBin();
  }

  /**
   * {@inheritdoc}
   */
  public function invalidateTags(array $tags) {
    if ($this->cacheBuffer->getCacheBackend() instanceof CacheTagsInvalidatorInterface) {
      return $this->cacheBuffer->invalidateTags($tags);
    }
    // XDebug cannot seem to include this last curly bracket in test coverage?
    // @codeCoverageIgnoreStart
  }

  // @codeCoverageIgnoreEnd

}
