<?php

/**
 * @file
 * Contains \Drupal\apiservices\CacheableApiTrait.
 */

namespace Drupal\apiservices;

use Drupal\Core\Cache\CacheBackendInterface;

/**
 * Provides an implementation of \Drupal\apiservices\CacheableApiInterface.
 */
trait CacheableApiTrait {

  /**
   * The maximum amount of time that an API response is considered "fresh".
   *
   * @var int
   */
  protected $cacheLifetime = 2592000;

  /**
   * The context to cache the API response with.
   *
   * @var string
   */
  protected $cacheContext = '';

  /**
   * {@inheritdoc}
   */
  abstract public function getCacheId();

  /**
   * {@inheritdoc}
   */
  public function getCacheLifetime() {
    return $this->cacheLifetime;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContext() {
    return $this->cacheContext;
  }

  /**
   * {@inheritdoc}
   */
  public function setCacheLifetime($lifetime) {
    if ($lifetime != CacheBackendInterface::CACHE_PERMANENT && $lifetime < 0) {
      throw new \DomainException('Cache lifetime must be CACHE_PERMANENT or an integer not less than 0');
    }
    $this->cacheLifetime = $lifetime;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setCacheContext($context) {
    $this->cacheContext = $context;
    return $this;
  }

}
