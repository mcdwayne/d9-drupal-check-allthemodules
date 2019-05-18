<?php

/**
 * @file
 * Cache part of performance.
 */

/**
 * Check availability of Page caching.
 */
function bs_performance_cache_caching() {
  //@TODO need to find this variable.
//  return variable_get('cache');
  return TRUE;
}

/**
 * Check availability of Varnish.
 */
function bs_performance_cache_varnish() {
  if (\Drupal::moduleHandler()->moduleExists('varnish')) {
    $statuses = varnish_get_status();

    foreach ($statuses as $status) {
      if (!$status) {
        return FALSE;
      }
    }

    return TRUE;
  }

  return FALSE;
}

/**
 * Check cache max age.
 */
function bs_performance_cache_max_age() {
  return \Drupal::config('system.performance')->get('cache.page.max_age') >= 300;
}

/**
 * Check availability of Memcache or Redis.
 */
function bs_performance_cache_memcache_redis() {
  return \Drupal::moduleHandler()->moduleExists('memcache')
  || \Drupal::moduleHandler()->moduleExists('redis')
  || \Drupal::moduleHandler()->moduleExists('memcache_storage');
}

/**
 * Check minimum cache lifetime.
 */
function bs_performance_cache_min_lifetime() {
  //@TODO need to find this variable.
//  return variable_get('cache_lifetime', 0) > 0;
  return TRUE;
}

/**
 * Check availability of Block caching.
 */
function bs_performance_cache_block() {
  //@TODO need to find this variable.
//  return variable_get('block_cache');
  return TRUE;
}
