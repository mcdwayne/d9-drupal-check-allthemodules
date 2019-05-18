<?php

namespace Drupal\ossfs;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;

/**
 * Defines the cached storage.
 *
 * The class gets another storage and a cache backend injected. It reads from
 * the cache and delegates the read to the storage on a cache miss. It also
 * handles cache invalidation.
 *
 * @see \Drupal\Core\Config\CachedStorage
 */
class OssfsCachedStorage implements OssfsStorageInterface {
  
  use DependencySerializationTrait;

  /**
   * The file metadata storage to be cached.
   *
   * @var \Drupal\ossfs\OssfsStorageInterface
   */
  protected $storage;

  /**
   * The instantiated Cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * List of listAll() prefixes with their results.
   *
   * @var array
   */
  protected $findByPrefixCache = [];

  /**
   * Constructs a new CachedStorage.
   *
   * @param \Drupal\ossfs\OssfsStorageInterface $storage
   *   A file metadata storage to be cached.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   A cache backend used to store file metadata.
   */
  public function __construct(OssfsStorageInterface $storage, CacheBackendInterface $cache) {
    $this->storage = $storage;
    $this->cache = $cache;
  }

  /**
   * {@inheritdoc}
   */
  public function exists($uri) {
    $cache_key = $this->getCacheKey($uri);
    if ($cache = $this->cache->get($cache_key)) {
      // The cache contains either the cached file metadata or FALSE if the
      // file metadata does not exist.
      return (bool) $cache->data;
    }
    // Read from the storage on a cache miss.
    return $this->storage->exists($uri);
  }

  /**
   * {@inheritdoc}
   */
  public function read($uri) {
    $cache_key = $this->getCacheKey($uri);
    if ($cache = $this->cache->get($cache_key)) {
      // The cache contains either the cached file metadata or FALSE if the
      // file metadata does not exist.
      return $cache->data;
    }
    // Read from the storage on a cache miss and cache the data. Also cache
    // information about missing file metadata.
    $data = $this->storage->read($uri);
    $this->cache->set($cache_key, $data);
    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function readMultiple(array $uris) {
    $data_to_return = [];

    $cache_keys_map = $this->getCacheKeys($uris);
    $cache_keys = array_values($cache_keys_map);
    $cached_list = $this->cache->getMultiple($cache_keys);

    if (!empty($cache_keys)) {
      // $cache_keys_map contains the full $uri => $cache_key map, while
      // $cache_keys contains just the $cache_key values that weren't found in
      // the cache.
      // @see \Drupal\Core\Cache\CacheBackendInterface::getMultiple()
      $uris_to_get = array_keys(array_intersect($cache_keys_map, $cache_keys));
      $list = $this->storage->readMultiple($uris_to_get);
      // Cache file metadata that were loaded from the storage, cache missing
      // file metadata as an explicit FALSE.
      $items = [];
      foreach ($uris_to_get as $uri) {
        $data = isset($list[$uri]) ? $list[$uri] : FALSE;
        $data_to_return[$uri] = $data;
        $items[$cache_keys_map[$uri]] = ['data' => $data];
      }

      $this->cache->setMultiple($items);
    }

    // Add the file metadata from the cache to the list.
    $cache_keys_inverse_map = array_flip($cache_keys_map);
    foreach ($cached_list as $cache_key => $cache) {
      $uri = $cache_keys_inverse_map[$cache_key];
      $data_to_return[$uri] = $cache->data;
    }

    // Ensure that only existing file metadata are returned, filter out cached
    // information about missing metadata.
    return array_filter($data_to_return);
  }

  /**
   * {@inheritdoc}
   */
  public function write($uri, array $data) {
    if ($this->storage->write($uri, $data)) {
      // While not all written data is read back, setting the cache instead of
      // just deleting it avoids cache rebuild stampedes.
      $this->cache->set($this->getCacheKey($uri), $data);
      $this->findByPrefixCache = [];
      return TRUE;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function delete($uri) {
    // If the cache was the first to be deleted, another process might start
    // rebuilding the cache before the storage is gone.
    if ($this->storage->delete($uri)) {
      $this->cache->delete($this->getCacheKey($uri));
      $this->findByPrefixCache = [];
      return TRUE;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function rename($uri, $new_uri) {
    // If the cache was the first to be deleted, another process might start
    // rebuilding the cache before the storage is renamed.
    if ($this->storage->rename($uri, $new_uri)) {
      $this->cache->delete($this->getCacheKey($uri));
      $this->cache->delete($this->getCacheKey($new_uri));
      $this->findByPrefixCache = [];
      return TRUE;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function listAll($prefix) {
    // Do not cache when a prefix is empty.
    if ($prefix !== '') {
      return $this->findByPrefix($prefix);
    }
    return $this->storage->listAll($prefix);
  }

  /**
   * Finds uris starting with a given prefix.
   *
   * @param string $prefix
   *   The prefix to search for
   *
   * @return array
   *   An array containing matching uris.
   */
  protected function findByPrefix($prefix) {
    $cache_key = $this->getCacheKey($prefix);
    if (!isset($this->findByPrefixCache[$cache_key])) {
      $this->findByPrefixCache[$cache_key] = $this->storage->listAll($prefix);
    }
    return $this->findByPrefixCache[$cache_key];
  }

  /**
   * Clears the static list cache.
   */
  public function resetListCache() {
    $this->findByPrefixCache = [];
  }

  /**
   * Returns a cache key for a uri.
   *
   * @param string $uri
   *   The uri.
   *
   * @return string
   *   The cache key for the uri.
   */
  protected function getCacheKey($uri) {
    return Crypt::hashBase64($uri);
  }

  /**
   * Returns a cache key map for an array of uris.
   *
   * @param array $uris
   *   The uris.
   *
   * @return array
   *   An array of cache keys keyed by uris.
   */
  protected function getCacheKeys(array $uris) {
    $cache_keys = array_map(function ($uri) {
      return Crypt::hashBase64($uri);
    }, $uris);

    return array_combine($uris, $cache_keys);
  }

}
