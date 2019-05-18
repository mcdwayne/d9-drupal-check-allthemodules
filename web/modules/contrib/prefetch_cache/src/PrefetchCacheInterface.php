<?php

/**
 * @file
 * Contains \Drupal\prefetch_cache\PrefetchCacheInterface.
 */

namespace Drupal\prefetch_cache;

/**
 * Provides an interface for prefetch caching.
 */
interface PrefetchCacheInterface {

  /**
   * Request key for prefetch caching requests.
   *
   */
  const PREFETCH_CACHE_REQUEST = 'prefetch_cache_request';

  /**
   * Request key for prefetch caching responses.
   */
  const PREFETCH_CACHE_TOKEN_ID = 'prefetch_cache_token_id';

  /**
   * Request key for allowing response to be cached.
   */
  const PREFETCH_CACHE_ALLOW_CACHING = 'prefetch_cache_allow_caching';

  /**
   * Request key for the generated cache tags.
   */
  const PREFETCH_CACHE_TAGS = 'prefetch_cache_tags';

  /**
   * Request key for the generated cache id.
   */
  const PREFETCH_CACHE_CACHE_ID = 'prefetch_cache_cache_id';
}