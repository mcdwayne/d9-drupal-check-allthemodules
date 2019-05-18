<?php

namespace Drupal\cache_consistent\Cache;

use Drupal\Core\Cache\CacheBackendInterface;

/**
 * Interface CacheConsistentBufferInterface.
 *
 * @package Drupal\cache_consistent\Cache
 *
 * @ingroup cache_consistent
 */
interface CacheConsistentBufferInterface extends CacheBackendInterface {

  /**
   * Get multiple cache items.
   *
   * If a cache item is found, the corresponding key must be unset in $cids.
   *
   * If a cache item has been marked as deleted, the cache object must be
   * set to FALSE
   *
   * If a cache item has been marked as invalid AND invalid items are NOT
   * allowed, the cache object must be set to FALSE
   *
   * If a cache item has been marked as invalid AND invalid items ARE allowed,
   * the cache object must be set to the previous cache object for that cache
   * id in the buffer. If no such object exists, no cache object should be
   * returned, and the corresponding cache id in $cids must remain.
   *
   * @param string[] &$cids
   *   The cache ids to get.
   * @param bool $allow_invalid
   *   Are invalid items allowed?
   *
   * @return array
   *   Array of cache items.
   */
  public function getMultiple(&$cids, $allow_invalid = FALSE);

  /**
   * Get the transaction depth via the transaction tracker this buffer is using.
   *
   * @return int
   *   The transaction depth.
   */
  public function transactionDepth();

  /**
   * Get the cache backend that this buffer uses.
   *
   * @return \Drupal\Core\Cache\CacheBackendInterface
   *   The cache backend.
   */
  public function getCacheBackend();

  /**
   * Get the transactional php indexer.
   *
   * @return \Gielfeldt\TransactionalPHP\Indexer|NULL
   *   The transactional php indexer instance or NULL.
   */
  public function getTransactionalPhpIndexer();

  /**
   * Invalidate tags.
   *
   * @param array $tags
   *   The tags to invalidate.
   */
  public function invalidateTags(array $tags);

}
