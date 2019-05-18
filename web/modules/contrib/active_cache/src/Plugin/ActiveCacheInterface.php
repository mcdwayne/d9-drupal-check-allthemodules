<?php

namespace Drupal\active_cache\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Cache\CacheableDependencyInterface;

/**
 * Defines an interface for Active cache plugins.
 */
interface ActiveCacheInterface extends PluginInspectionInterface, CacheableDependencyInterface {

  /**
   * Builds the required data, caches it, and returns it.
   *
   * @return mixed
   */
  public function buildCache();

  /**
   * Searches for the cache.
   *
   * @return object|false
   *   the cache object if found, false otherwise
   */
  public function getCache();

  /**
   * Tries to load the data from the cache (first static then real).
   * If the cache does not exist then it will be rebuilt and returned.
   *
   * @return mixed
   */
  public function getData();

  /**
   * @return bool
   *   Is the data currently being cached?
   */
  public function isCached();

  /**
   * @return string
   */
  public function getCacheId();
}
