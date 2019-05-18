<?php

/**
 * @file
 * Contains \Drupal\prefetch_cache\StackMiddleware\PrefetchCacheAfterSessionInitialized.
 */

namespace Drupal\prefetch_cache\StackMiddleware;

use Drupal\page_cache\StackMiddleware\PageCache;
use Drupal\Core\PageCache\RequestPolicyInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Drupal\prefetch_cache\PrefetchCacheInterface;

/**
 * Executes the prefetch caching before the main kernel takes over the request.
 *
 * Runs after PrefetchCacheBeforePageCache, PageCache and Session.
 *
 */
class PrefetchCacheAfterSessionInitialized extends PageCache {

  public function handle(Request $request, $type = self::MASTER_REQUEST, $catch = TRUE) {
    $response = NULL;
    $is_cache_request = $request->attributes->has(PrefetchCacheInterface::PREFETCH_CACHE_REQUEST);
    $has_token_id = $request->attributes->has(PrefetchCacheInterface::PREFETCH_CACHE_TOKEN_ID);

    if (!($is_cache_request || $has_token_id)) {
      $response = $this->pass($request, $type, $catch);
    }
    elseif ($is_cache_request && $type === static::MASTER_REQUEST && $this->requestPolicy->check($request) === RequestPolicyInterface::ALLOW) {
      // A new prefetch cache entry is requested. Therefor cache ::get triggered
      // by ::lookup will always fail. But instead of creating a corresponding
      // entry via ::set() we only prepare the requireed IDs that will be used
      // by PrefetchCacheBeforePageCache later to perform the real action.
      $response = $this->lookup($request, $type, $catch);
      // 'X-Drupal-Cache' is going to be set by PageCache. If PageCache had a
      // Cache HIT our ::handle will not be executed, but if it was a MISS, our
      // handle will be executed. We extend from PageCache and call the lookup
      // function and so the 'X-Drupal-Cache' header key might be set. But
      // we want this header key to be set only by PageCache directly, because
      // it indicates a cache entry by PageCache, and we check this on the way
      // back in PrefetchCacheBeforePageCache.
      $response->headers->remove('X-Drupal-Cache');
    }
    elseif ($has_token_id) {
      $response = $this->get($request) ?: $this->pass($request, $type, $catch);
    }
    else {
      $response = $this->pass($request, $type, $catch);
    }

    return $response;
  }

  /**
   * {@inheritdoc}
   */
  protected function set(Request $request, Response $response, $expire, array $tags) {
    // Do not cache here, leave the caching to PrefetchCacheBeforePageCache,
    // to ensure only page cache or prefetch cache is caching!
    $request->attributes->set(PrefetchCacheInterface::PREFETCH_CACHE_ALLOW_CACHING, TRUE);
    $request->attributes->set(PrefetchCacheInterface::PREFETCH_CACHE_TAGS, $tags);
  }

  /**
   * {@inheritdoc}
   */
  protected function getCacheId(Request $request)  {
    $cache_id = $request->attributes->get(PrefetchCacheInterface::PREFETCH_CACHE_CACHE_ID);
    if (!$cache_id) {

      $token_id = $request->attributes->get(PrefetchCacheInterface::PREFETCH_CACHE_TOKEN_ID) ?: uniqid();

      $cid_parts = [
        'uid-' . $request->getSession()->get('uid'),
        parent::getCacheId($request),
        $token_id,
      ];

      $cache_id = implode(':', $cid_parts);

      $request->attributes->set(PrefetchCacheInterface::PREFETCH_CACHE_TOKEN_ID, $token_id);
      $request->attributes->set(PrefetchCacheInterface::PREFETCH_CACHE_CACHE_ID, $cache_id);
    }
    return $cache_id;
  }

}
