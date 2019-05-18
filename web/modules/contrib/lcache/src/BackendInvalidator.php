<?php

/**
 * @file
 * Contains \Drupal\lcache\BackendInvalidator.
 */

namespace Drupal\lcache;

use Drupal\Core\Cache\CacheTagsInvalidatorInterface;

/**
 * Invalidates LCache tags.
 */
class BackendInvalidator implements CacheTagsInvalidatorInterface {

  protected $integrated;

  /**
   * Constructs an invalidator object.
   *
   * @param \LCache\Integrated $integrated
   *   The integrated Cache object were invalidations will be run.
   */
  public function __construct(\LCache\Integrated $integrated) {
    $this->integrated = $integrated;
  }

  /**
   * {@inheritdoc}
   */
  public function invalidateTags(array $tags) {
    foreach ($tags as $tag) {
      $this->integrated->deleteTag($tag);
    }
  }
}
