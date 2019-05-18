<?php

namespace Drupal\cache_consistent\Cache;

/**
 * Class CacheConsistentScrubberManager.
 *
 * @package Drupal\cache_consistent\Cache
 */
class CacheConsistentScrubberManager {

  /**
   * Cache scrubbers.
   *
   * @var \Drupal\cache_consistent\Cache\CacheConsistentScrubberInterface[]
   */
  protected $cacheScrubbers = [];

  /**
   * Add a cache scrubber.
   *
   * @param \Drupal\cache_consistent\Cache\CacheConsistentScrubberInterface $cache_scrubber
   *   Cache scrubber.
   */
  public function addCacheScrubber(CacheConsistentScrubberInterface $cache_scrubber) {
    $this->cacheScrubbers[] = $cache_scrubber;
  }

  /**
   * Scrub operations in cache buffer.
   *
   * @param array $operations
   *   The operations to scrub.
   *
   * @return array
   *   The operations remaining after scrubbing.
   */
  public function scrub($operations) {
    foreach ($this->cacheScrubbers as $cacheScrubber) {
      $operations = $cacheScrubber->scrub($operations);
    }
    return $operations;
  }

}
