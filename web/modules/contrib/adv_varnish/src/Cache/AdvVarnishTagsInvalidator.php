<?php

/**
 * @file
 * Contains \Drupal\adv_varnish\Cache\AdvVarnishTagsInvalidator.
 */

namespace Drupal\adv_varnish\Cache;

use Drupal\adv_varnish\AdvVarnishInterface;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Logger\RfcLogLevel;


class AdvVarnishTagsInvalidator implements CacheTagsInvalidatorInterface {

  public $varnishHandler;

  /**
   * Marks cache items with any of the specified tags as invalid.
   *
   * @param string[] $tags
   *   The list of tags for which to invalidate cache items.
   */
  public function invalidateTags(array $tags) {
    $this->varnishHandler->purgeTags($tags);
  }
}
