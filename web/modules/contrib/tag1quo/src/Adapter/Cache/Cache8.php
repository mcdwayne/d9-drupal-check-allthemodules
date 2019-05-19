<?php

namespace Drupal\tag1quo\Adapter\Cache;

use Drupal\Core\Cache\CacheBackendInterface;

/**
 * Class Cache8.
 *
 * @internal This class is subject to change.
 */
class Cache8 extends Cache {

  /**
   * {@inheritdoc}
   */
  const PERMANENT = CacheBackendInterface::CACHE_PERMANENT;

  /**
   * The Cache bin.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * {@inheritdoc}
   */
  protected $defaultBin = 'default';

  /**
   * {@inheritdoc}
   */
  public function __construct($bin = NULL) {
    parent::__construct($bin);
    $this->cache = \Drupal::cache($this->bin);
  }

  /**
   * {@inheritdoc}
   */
  public function get($cid) {
    return $this->cache->get($cid);
  }

  /**
   * {@inheritdoc}
   */
  public function set($cid, $data, $expire = self::PERMANENT, array $tags = array()) {
    $this->cache->set($cid, $data, $expire, $tags);
    return $this;
  }

}
