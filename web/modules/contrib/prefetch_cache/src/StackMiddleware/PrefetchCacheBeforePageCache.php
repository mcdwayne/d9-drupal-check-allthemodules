<?php

/**
 * @file
 * Contains \Drupal\prefetch_cache\StackMiddleware\PrefetchCacheBeforePageCache.
 */

namespace Drupal\prefetch_cache\StackMiddleware;

use Drupal\Component\Serialization\Json;
use Drupal\page_cache\StackMiddleware\PageCache;
use Drupal\Core\Cache\CacheBackendInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Drupal\prefetch_cache\PrefetchCacheInterface;
use Drupal\prefetch_cache\Cache\ChainCacheTagsGeneratorInterface;
use Drupal\Core\Cache\Cache;

/**
 * Executes the prefetch caching before the main kernel takes over the request.
 *
 * Runs before PageCache.
 *
 */
class PrefetchCacheBeforePageCache extends PageCache {

  /**
   * The cache tags generator.
   *
   * @var \Drupal\prefetch_cache\Cache\ChainCacheTagsGeneratorInterface.
   */
  protected $cacheTagsGenerator;

  /**
   * Constructs a PrefetchCache object.
   *
   * @param \Symfony\Component\HttpKernel\HttpKernelInterface $http_kernel
   *   The decorated kernel.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache bin.
   * @param \Drupal\prefetch_cache\Cache\ChainCacheTagsGeneratorInterface $cache_tags_generator
   *   A cache id generator.
   */
  public function __construct(HttpKernelInterface $http_kernel, CacheBackendInterface $cache, ChainCacheTagsGeneratorInterface $cache_tags_generator) {
    $this->httpKernel = $http_kernel;
    $this->cache = $cache;
    $this->cacheTagsGenerator = $cache_tags_generator;
  }

  /**
   * {@inheritdoc}
   */
  public function handle(Request $request, $type = self::MASTER_REQUEST, $catch = TRUE) {
    $response = NULL;
    $is_cache_request = $request->query->has(PrefetchCacheInterface::PREFETCH_CACHE_REQUEST);
    $has_token_id = $request->query->has(PrefetchCacheInterface::PREFETCH_CACHE_TOKEN_ID);

    if (!($is_cache_request || $has_token_id)) {
      $response = $this->pass($request, $type, $catch);
    }
    elseif ($is_cache_request) {
      // Remove the query parameter to allow for regular handling by other
      // services and add it as an attribute instead.
      $this->removeQueryParameter($request, PrefetchCacheInterface::PREFETCH_CACHE_REQUEST, 1);
      $request->attributes->set(PrefetchCacheInterface::PREFETCH_CACHE_REQUEST, 1);

      // Execute the http kernel handle chain.
      $response = $this->httpKernel->handle($request, $type, $catch);

      // The response has been cached by page cache.

      if ($response->headers->contains('X-Drupal-Cache', 'HIT') || $response->headers->contains('X-Drupal-Cache', 'MISS')) {
        $response->setContent(Json::encode(['cached_by_page_cache' => TRUE]));
      }
      // Prefetch Cache was able to generate a response.
      elseif ($request->attributes->has(PrefetchCacheInterface::PREFETCH_CACHE_ALLOW_CACHING)) {
        $expire_timestamp = $this->getExpireTimestamp();

        $expires = $response->getExpires();
        $expires->setTimestamp($expire_timestamp);
        $response->setExpires($expires);

        $cache_tags = Cache::mergeTags(
          $request->attributes->get(PrefetchCacheInterface::PREFETCH_CACHE_TAGS),
          $this->cacheTagsGenerator->generate($request)
        );

        $this->set($request, $response, $expire_timestamp, $cache_tags);

        $token_id = $request->attributes->get(PrefetchCacheInterface::PREFETCH_CACHE_TOKEN_ID);
        $response->setContent(Json::encode([PrefetchCacheInterface::PREFETCH_CACHE_TOKEN_ID => $token_id]));
      }
      else {
        $response->setContent(Json::encode(['not_cached' => TRUE]));
      }
    }
    elseif ($has_token_id) {
      $token_id = $request->query->get(PrefetchCacheInterface::PREFETCH_CACHE_TOKEN_ID);
      $this->removeQueryParameter($request, PrefetchCacheInterface::PREFETCH_CACHE_TOKEN_ID, $token_id);
      $request->attributes->add([PrefetchCacheInterface::PREFETCH_CACHE_TOKEN_ID => $token_id]);

      // PrefetchCacheAfterSessionInitialized will check the user's access
      // rights and deliver the response from the cache if possible instead of
      // executing the whole http kernel chain.
      $response = $this->pass($request, $type, $catch);
    }

    return $response;
  }

  /**
   * {@inheritdoc}
   */
  protected function getCacheId(Request $request)  {
    return $request->attributes->get(PrefetchCacheInterface::PREFETCH_CACHE_CACHE_ID);
  }

  /**
   * Get the expiration timestamp for response header and cache entry.
   *
   * The timestamp will calculated from the REQUEST_TIME and the time to live
   * set in 'prefetch_cache.settings'.
   *
   * @return int
   *   Expiration timestamp.
   */
  protected function getExpireTimestamp() {
    return REQUEST_TIME + \Drupal::config('prefetch_cache.settings')->get('time_to_live');
  }

  /**
   * Removes a query parameter from query string.
   *
   * @param $query_string
   * @param $param
   * @return string
   */
  protected function removeParamFromQueryString($query_string, $param) {
    $variables = [];
    parse_str($query_string, $variables);
    unset($variables[$param]);
    $query_string = http_build_query($variables);
    return $query_string;
  }

  /**
   * Removes a query parameter from the query and server parameter bag.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   * @param $query_param
   * @param $value
   */
  protected function removeQueryParameter(Request $request, $query_param, $value) {
    $request_uri = $request->server->get('REQUEST_URI');
    $request_uri = str_replace($query_param . '=' . $value, '', $request_uri);
    if (substr($request_uri, -1) == '?') {
      $request_uri = rtrim($request_uri, '?');
    }
    $request->server->set('REQUEST_URI', $request_uri);

    $request->query->remove($query_param);
    $query_string = $request->server->get('QUERY_STRING');
    $query_string = $this->removeParamFromQueryString($query_string, $query_param);
    $request->server->set('QUERY_STRING', $query_string);
  }
}
