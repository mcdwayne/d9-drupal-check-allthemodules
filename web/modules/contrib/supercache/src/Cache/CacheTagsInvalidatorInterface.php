<?php

/**
 * @file
 * Contains \Drupal\supercache\Cache\CacheTagsInvalidatorInterface.
 */

namespace Drupal\supercache\Cache;

/**
 * Extends core tag invalidtor interface.
 *
 * @ingroup cache
 */
interface CacheTagsInvalidatorInterface extends \Drupal\Core\Cache\CacheTagsInvalidatorInterface {

  /**
   * Reset all tag values.
   */
  public function resetTags();

}
