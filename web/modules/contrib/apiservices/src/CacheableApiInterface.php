<?php

/**
 * @file
 * Contains \Drupal\apiservices\CacheableApiInterface.
 */

namespace Drupal\apiservices;

/**
 * Defines an interface for cacheable API endpoints.
 */
interface CacheableApiInterface {

  /**
   * Gets a cache ID for this request.
   *
   * @return string
   *   A cache ID created from the current cache context and request URL.
   *
   * @see CacheableApiInterface::getCacheContext()
   */
  public function getCacheId();

  /**
   * Gets the amount of time that the API response should be cacheable.
   *
   * @return int
   *   The duration to cache the API response.
   *
   * @see CacheableApiInterface::setCacheLifetime()
   */
  public function getCacheLifetime();

  /**
   * Gets the context used to cache the API response under.
   *
   * @return string
   *   The cache context.
   *
   * @see CacheableApiInterface::setCacheContext()
   */
  public function getCacheContext();

  /**
   * Sets the amount of time that the API response should be cacheable.
   *
   * When using non-standard cache lifetime values, always provide a context to
   * use when caching the response. This ensures that requests made from other
   * functions and modules do not become invalid at unexpected times.
   *
   * @param int $lifetime
   *   The duration to cache the API response.
   *
   * @see CacheableApiInterface::setCacheContext()
   */
  public function setCacheLifetime($lifetime);

  /**
   * Sets the context to cache the API response under.
   *
   * @param string $context
   *   A context that is prefixed to the response's cache ID.
   *
   * @see CacheableApiInterface::setCacheLifetime()
   */
  public function setCacheContext($context);

}
