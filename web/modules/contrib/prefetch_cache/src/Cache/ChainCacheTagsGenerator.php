<?php

/**
 * @file
 * Contains \Drupal\prefetch_cache\Cache\ChainCacheTagsGenerator.
 */

namespace Drupal\prefetch_cache\Cache;

use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Cache\Cache;

/**
 * Implements a compound cache tags generator.
 *
 */
class ChainCacheTagsGenerator implements ChainCacheTagsGeneratorInterface {

  /**
   * A list of generators used to generate cache tags for the cache entry.
   *
   * @var \Drupal\prefetch_cache\Cache\CacheTagsGeneratorInterface[]
   */
  protected $generators = [];

  /**
   * @inheritdoc
   */
  public function generate(Request $request) {
    $final_result = [];

    foreach ($this->generators as $generator) {
      $result = $generator->generate($request);
      if ($result) {
        $final_result = Cache::mergeTags($final_result, $result);
      }
    }

    return $final_result;
  }

  /**
   * @inheritdoc
   */
  public function addGenerator(CacheTagsGeneratorInterface $generator) {
    $this->generators[] = $generator;
    return $this;
  }

}
