<?php

namespace Drupal\doctrine_cache\Cache;

use Doctrine\Common\Cache\CacheProvider;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;

/**
 * Provides a Drupal cache backend for the doctrine cache.
 */
class DrupalDoctrineCache extends CacheProvider {

  /**
   * The Drupal cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * The cache tags to set for all items.
   *
   * @var string[]
   */
  protected $tags;

  /**
   * Constructs a new DrupalDoctrineCache.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The Drupal cache backend.
   * @param string[] $tags
   *   An array of cache tags to set for all items.
   */
  public function __construct(CacheBackendInterface $cache = NULL, array $tags = []) {
    $this->cache = $cache ?: \Drupal::cache();
    $this->tags = $tags;
  }

  /**
   * Set the cache backend.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend.
   *
   * @return static
   */
  public function setCacheBackend(CacheBackendInterface $cache) {
    $this->cache = $cache;
  }

  /**
   * {@inheritdoc}
   */
  protected function doContains($id) {
    return (bool) $this->cache->get($id);
  }

  /**
   * {@inheritdoc}
   */
  protected function doFetch($id) {
    $item = $this->cache->get($id);
    return $item ? $item->data : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  protected function doFetchMultiple(array $keys) {
    $items = $this->cache->getMultiple($keys);
    return array_map(function($item) {
      return $item->data;
    }, $items);
  }

  /**
   * {@inheritdoc}
   */
  protected function doSave($id, $data, $lifeTime = 0) {
    $this->cache->set($id, $data, $lifeTime ?: Cache::PERMANENT, $this->tags);
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  protected function doSaveMultiple(array $keysAndValues, $lifetime = 0) {
    $items = [];
    foreach ($keysAndValues as $key => $value) {
      $items[$key] = [
        'data' => $value,
        'expire' => $lifetime ?: Cache::PERMANENT,
        'tags' => $this->tags,
      ];
    }
    $this->cache->setMultiple($items);
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  protected function doDelete($id) {
    $this->cache->delete($id);
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  protected function doFlush() {
    $this->cache->deleteAll();
    $this->cache->garbageCollection();
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  protected function doGetStats() {
    return NULL;
  }

}
