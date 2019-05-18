<?php

namespace Drupal\cache_consistent\Cache;

/**
 * CacheBufferAware trait.
 *
 * @author Thomas Gielfeldt <thomas@gielfeldt.dk>
 *
 * @codeCoverageIgnore
 *   Too simple to test.
 */
trait CacheBufferAwareTrait {
  /**
   * The cache buffer.
   *
   * @var CacheConsistentBufferInterface
   */
  protected $cacheBuffer = NULL;

  /**
   * Sets the cache buffer.
   *
   * @param CacheConsistentBufferInterface|NULL $cache_buffer
   *   A CacheConsistentBufferInterface instance or NULL.
   */
  public function setCacheBuffer(CacheConsistentBufferInterface $cache_buffer = NULL) {
    $this->cacheBuffer = $cache_buffer;
  }

  /**
   * Get the cache buffer.
   *
   * @return CacheConsistentBufferInterface|NULL
   *   A CacheConsistentBufferInterface instance or NULL.
   */
  public function getCacheBuffer() {
    return $this->cacheBuffer;
  }

}
