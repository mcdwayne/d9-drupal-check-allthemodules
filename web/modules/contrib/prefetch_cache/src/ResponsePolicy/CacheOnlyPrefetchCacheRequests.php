<?php

/**
 * @file
 * Contains \Drupal\prefetch_cache\ResponsePolicy\CacheOnlyPrefetchCacheRequests.
 */

namespace Drupal\prefetch_cache\ResponsePolicy;

use Drupal\Core\PageCache\ResponsePolicyInterface;
use Drupal\prefetch_cache\PrefetchCacheInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * A policy denying delivery of cached pages if not an prefetch response used.
 */
class CacheOnlyPrefetchCacheRequests implements ResponsePolicyInterface {

  /**
   * {@inheritdoc}
   */
  public function check(Response $response, Request $request) {
    if (!$request->attributes->has(PrefetchCacheInterface::PREFETCH_CACHE_REQUEST)) {
      return static::DENY;
    }
  }

}
