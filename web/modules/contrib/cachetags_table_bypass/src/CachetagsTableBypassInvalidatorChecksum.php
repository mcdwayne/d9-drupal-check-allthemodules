<?php

namespace Drupal\cachetags_table_bypass;

use Drupal\Core\Cache\DatabaseCacheTagsChecksum;

/**
 * CachetagsTableBypassInvalidatorChecksum service.
 */
class CachetagsTableBypassInvalidatorChecksum extends DatabaseCacheTagsChecksum {

  /**
   * {@inheritdoc}
   */
  public function invalidateTags(array $tags) {
    foreach ($tags as $tag) {
      // Only invalidate tags once per request unless they are written again.
      if (isset($this->invalidatedTags[$tag])) {
        continue;
      }
      $this->invalidatedTags[$tag] = TRUE;
      if (empty($this->tagCache[$tag])) {
        $this->tagCache[$tag] = 0;
      }
      $this->tagCache[$tag]++;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCurrentChecksum(array $tags) {
    foreach ($tags as $tag) {
      unset($this->invalidatedTags[$tag]);
    }
    return $this->calculateChecksum($tags);
  }

  /**
   * {@inheritdoc}
   */
  protected function calculateChecksum(array $tags) {
    $checksum = 0;
    foreach ($tags as $tag) {
      if (empty($this->tagCache[$tag])) {
        $this->tagCache[$tag] = 0;
      }
      $checksum += $this->tagCache[$tag];
    }

    if ($checksum) {
      return microtime(TRUE) * 1000;
    }
    return $checksum;
  }

}
