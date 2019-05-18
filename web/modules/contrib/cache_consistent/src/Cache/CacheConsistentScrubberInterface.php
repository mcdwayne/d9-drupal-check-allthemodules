<?php

namespace Drupal\cache_consistent\Cache;

/**
 * Interface CacheConsistentScrubberInterface.
 *
 * @package Drupal\cache_consistent\Cache
 *
 * @ingroup cache_consistent
 */
interface CacheConsistentScrubberInterface {

  /**
   * Scrub operations.
   *
   * @param \Gielfeldt\TransactionalPHP\Operation[] $operations
   *   The operations to scrub.
   *
   * @return \Gielfeldt\TransactionalPHP\Operation[]
   *   The operations remaining after scrubbing.
   */
  public function scrub($operations);

}
