<?php

namespace Drupal\odoo_api\OdooApi\Util;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;

/**
 * Odoo API response cache trait.
 */
trait ResponseCacheTrait {

  /**
   * Cache tag service.
   *
   * @var string
   */
  protected $responseCacheTag;

  /**
   * The cache service.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * The cache tags invalidator.
   *
   * @var \Drupal\Core\Cache\CacheTagsInvalidatorInterface
   */
  protected $cacheTagsInvalidator;

  /**
   * Set cache options.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_service
   *   Cache service.
   * @param \Drupal\Core\Cache\CacheTagsInvalidatorInterface $cache_tags_invalidator
   *   Cache invalidator service.
   * @param string $response_cache_tag
   *   Response cache tag.
   */
  protected function setCacheOptions(CacheBackendInterface $cache_service, CacheTagsInvalidatorInterface $cache_tags_invalidator, $response_cache_tag) {
    $this->cache = $cache_service;
    $this->cacheTagsInvalidator = $cache_tags_invalidator;
    $this->responseCacheTag = $response_cache_tag;
  }

  /**
   * Fetches metadata from Odoo, trying to use cache.
   *
   * @param string $cache_key
   *   Cache key.
   * @param callable $callback
   *   Callback for getting the value.
   *
   * @return mixed
   *   Callback return result.
   */
  protected function cacheResponse($cache_key, callable $callback) {
    $cache_key = 'odoo_api:response_cache:' . $cache_key;

    if ($cache = $this->cache->get($cache_key)) {
      $data = $cache->data;
    }
    else {
      $data = call_user_func($callback);
      // 1 day cache.
      $this->cache->set($cache_key, $data, \Drupal::time()->getRequestTime() + 86400, ['config:odoo_api.api_client', $this->responseCacheTag]);
    }

    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function invalidateCache() {
    $this->cacheTagsInvalidator->invalidateTags([$this->responseCacheTag]);
  }

}
