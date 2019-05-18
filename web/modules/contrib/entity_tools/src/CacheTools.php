<?php

namespace Drupal\entity_tools;

/**
 * Class CacheTools.
 *
 * Utilities for basic caching.
 *
 * @package Drupal\entity_tools
 */
class CacheTools {

  /**
   * Removes any caching for a render array.
   *
   * @todo refactor reference paramater with method chaining
   *
   * @param array $build
   *   Render array.
   */
  public static function setNoCache(array &$build) {
    $build['#cache']['max-age'] = 0;
  }

}
