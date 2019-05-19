<?php

/**
 * @file
 * Contains \Drupal\supercache\Cache\CacheRawFactoryInterface.
 */

namespace Drupal\supercache\Cache;

/**
 * An interface defining cache factory classes.
 */
interface CacheRawFactoryInterface {

  /**
   * Gets a cache backend class for a given cache bin.
   *
   * @param string $bin
   *   The cache bin for which a cache backend object should be returned.
   *
   * @return \Drupal\supercache\Cache\CacheRawBackendInterface
   *   The cache backend object associated with the specified bin.
   */
  public function get($bin);

}
