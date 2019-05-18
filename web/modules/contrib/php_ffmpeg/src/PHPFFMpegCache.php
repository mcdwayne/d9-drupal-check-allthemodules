<?php

namespace Drupal\php_ffmpeg;

use Doctrine\Common\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;

/**
 * Adapter between Doctrine cache needed by FFMPeg library and Drupal cache.
 */
class PHPFFMpegCache implements Cache {

  /**
   * The cache backend that should be used.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * Prefix for the cache ids.
   *
   * @var string
   */
  protected $prefix;

  /**
   * Constructs a CacheCollector object.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend.
   * @param string $prefix
   *   Prefix used for appending to cached item identifiers.
   */
  public function __construct(CacheBackendInterface $cache, $prefix) {
    $this->cache = $cache;
    $this->prefix = (string) $prefix;
  }

  /**
   * @inheritdoc
   */
  public function fetch($id) {
    return $this->cache->get($this->getCid($id));
  }

  /**
   * @inheritdoc
   */
  public function contains($id) {
    return !!$this->cache->get($this->getCid($id));
  }

  /**
   * @inheritdoc
   */
  public function save($id, $data, $lifeTime = 0) {
    $this->cache->set($this->getCid($id), $data, time() + $lifeTime);
  }

  /**
   * @inheritdoc
   */
  public function delete($id) {
    $this->cache->delete($this->getCid($id));
  }

  /**
   * @inheritdoc
   */
  public function getStats() {
    return NULL;
  }

  /**
   * Returns a prefixed cache id based on given id.
   *
   * @param string $id
   *   The id string to prefix.
   *
   * @return string
   *   A prefixed cache id.
   */
  protected function getCid($id) {
    return "{$this->prefix}:{$id}";
  }

}
