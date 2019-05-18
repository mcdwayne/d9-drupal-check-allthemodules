<?php

namespace Drupal\guzzle_cache;

use Kevinrob\GuzzleCache\Storage\CacheStorageInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Kevinrob\GuzzleCache\CacheEntry;

/**
 * Provides a Drupal cache backend for the Guzzle caching middleware.
 */
class DrupalGuzzleCache implements CacheStorageInterface {

  /**
   * The Drupal cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * The cache key prefix.
   *
   * @var string
   */
  protected $prefix = '';

  /**
   * The cache tags to set for all items.
   *
   * @var string[]
   */
  protected $tags = [];

  /**
   * Constructs a new DrupalGuzzleCache.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The Drupal cache backend.
   * @param string $prefix
   *   The cache key prefix to use for all items.
   * @param string[] $tags
   *   The array of cache tags to set for all items.
   */
  public function __construct(CacheBackendInterface $cache, $prefix = 'guzzle:', array $tags = []) {
    $this->cache = $cache;
    $this->setPrefix($prefix);
    $this->tags = $tags;
  }

  /**
   * Set the cache key prefix.
   *
   * @param string $prefix
   *   The cache key prefix, must not be longer than 191 characters.
   *
   * @return static
   */
  protected function setPrefix($prefix) {
    // Cache keys are 64 characters, and the default cache cid length is 255.
    if (strlen($prefix) > 191) {
      throw new \InvalidArgumentException('The cache key prefix cannot be longer than 191 characters.');
    }
    $this->prefix = $prefix;
    return $this;
  }

  /**
   * Prefix a cache key.
   *
   * @param string $key
   *   The cache key to prefix.
   *
   * @return string
   *   The complete cache key.
   */
  public function prefix($key) {
    return $this->prefix . $key;
  }

  /**
   * {@inheritdoc}
   */
  public function fetch($key) {
    $key = $this->prefix($key);
    $item = $this->cache->get($key);
    return $item ? $item->data : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function save($key, CacheEntry $data) {
    $key = $this->prefix($key);
    $expires = $data->getStaleAt()->getTimestamp();
    $this->cache->set($key, $data, $expires, $this->tags);
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function delete($key) {
    $key = $this->prefix($key);
    $this->cache->delete($key);
    return TRUE;
  }

}
