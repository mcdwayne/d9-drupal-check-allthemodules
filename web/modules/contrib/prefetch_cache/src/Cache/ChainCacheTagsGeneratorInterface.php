<?php

/**
 * @file
 * Contains \Drupal\prefetch_cache\Cache\ChainCacheTagsGeneratorInterface.
 */

namespace Drupal\prefetch_cache\Cache;

use Symfony\Component\HttpFoundation\Request;

/**
 * Provides an interface for prefetch caching.
 *
 */
interface ChainCacheTagsGeneratorInterface {

  /**
   * Generate the cache id parts.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *
   * @return array
   *   Array of cache id parts.
   */
  public function generate(Request $request);

  /**
   * Add a generator to the list of chain id generators.
   *
   * @param \Drupal\prefetch_cache\Cache\CacheTagsGeneratorInterface.php $generator
   *   The cache id generator to add.
   *
   * @return $this
   */
  public function addGenerator(CacheTagsGeneratorInterface $generator);
}