<?php

namespace Drupal\cache_consistent\Cache;

/**
 * Class CacheConsistentIsolationLevel.
 *
 * Defines isolation level constants for Cache Consistent.
 *
 * @package Drupal\cache_consistent\Cache
 */
class CacheConsistentIsolationLevel {
  /**
   * Isolation level: READ-UNCOMMITTED.
   */
  const READ_UNCOMMITTED = 0;

  /**
   * Isolation level: UNCOMMITTED.
   */
  const READ_COMMITTED   = 1;

  /**
   * Isolation level: REPEATABLE-READ.
   */
  const REPEATABLE_READ  = 2;

  /**
   * Isolation level: SERIALIZABLE.
   */
  const SERIALIZABLE     = 3;

  /**
   * Default isolation level: REPEATABLE-READ.
   */
  const DEFAULT_LEVEL    = 2;

}
