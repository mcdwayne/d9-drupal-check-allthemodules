<?php

namespace Drupal\cache_consistent\Cache;

use Drupal\Core\Cache\CacheBackendInterface;

/**
 * CacheBackendAware trait.
 *
 * @author Thomas Gielfeldt <thomas@gielfeldt.dk>
 *
 * @codeCoverageIgnore
 *   Too simple to test.
 */
trait CacheBackendAwareTrait {
  /**
   * The cache backend.
   *
   * @var CacheBackendInterface
   */
  protected $cacheBackend = NULL;

  /**
   * Sets the cache backend.
   *
   * @param CacheBackendInterface|NULL $cache_backend
   *   A CacheBackendInterface instance or NULL.
   */
  public function setCacheBackend(CacheBackendInterface $cache_backend = NULL) {
    $this->cacheBackend = $cache_backend;
  }

  /**
   * Get the cache backend.
   *
   * @return CacheBackendInterface|NULL
   *   A CacheBackendInterface instance or NULL.
   */
  public function getCacheBackend() {
    return $this->cacheBackend;
  }

}
