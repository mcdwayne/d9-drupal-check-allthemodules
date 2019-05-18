<?php

/**
 * @file
 * Contains \Drupal\prefetch_cache\Cache\CacheTagsGeneratorInterface.
 */

namespace Drupal\prefetch_cache\Cache;

use Symfony\Component\HttpFoundation\Request;

/**
 * Defines the interface for cache tags generators implementations.
 *
 */
interface CacheTagsGeneratorInterface {

  /**
   * Generates cache tags.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The incoming request object.
   *
   * @return array
   *   Array of cache id parts.
   */
  public function generate(Request $request);

}
