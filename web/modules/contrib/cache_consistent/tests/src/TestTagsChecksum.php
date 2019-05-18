<?php

namespace Drupal\Tests\cache_consistent;

use Drupal\Core\Cache\CacheTagsChecksumInterface;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;

/**
 * Cache tags invalidations checksum implementation that uses the database.
 */
class TestTagsChecksum implements CacheTagsChecksumInterface, CacheTagsInvalidatorInterface {

  /**
   * A list of tags that have already been invalidated in this request.
   *
   * Used to prevent the invalidation of the same cache tag multiple times.
   *
   * @var array
   */
  protected $invalidatedTags = array();

  /**
   * {@inheritdoc}
   */
  public function invalidateTags(array $tags) {
    foreach ($tags as $tag) {
      $this->invalidatedTags[$tag] = isset($this->invalidatedTags[$tag]) ? $this->invalidatedTags[$tag] + 1 : 1;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCurrentChecksum(array $tags) {
    return $this->calculateChecksum($tags);
  }

  /**
   * {@inheritdoc}
   */
  public function isValid($checksum, array $tags) {
    return $checksum == $this->calculateChecksum($tags);
  }

  /**
   * Calculates the current checksum for a given set of tags.
   *
   * @param array $tags
   *   The array of tags to calculate the checksum for.
   *
   * @return int
   *   The calculated checksum.
   */
  protected function calculateChecksum(array $tags) {
    $checksum = 0;
    foreach ($tags as $tag) {
      $checksum += isset($this->invalidatedTags[$tag]) ? $this->invalidatedTags[$tag] : 0;
    }
    return $checksum;
  }

  /**
   * {@inheritdoc}
   *
   * @codeCoverageIgnore
   */
  public function reset() {
    $this->invalidatedTags = array();
  }

}
