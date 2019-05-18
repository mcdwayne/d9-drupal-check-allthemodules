<?php

/**
 * @file
 * Contains \Drupal\supercache\Cache\CacheCacheTagsChecksum.
 */

namespace Drupal\supercache\Cache;

use Drupal\supercache\Cache\CacheTagsInvalidatorInterface;

use Drupal\Core\Cache\CacheTagsChecksumInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\CacheFactoryInterface;

/**
 * Cache tags invalidations checksum implementation that uses
 * a CacheRawBackendInterface as the storage.
 */
class CacheCacheTagsChecksum implements CacheTagsChecksumInterface, CacheTagsInvalidatorInterface {

  /**
   * Backend storage
   *
   * @var CacheRawBackendInterface
   */
  protected $backend = NULL;


  /**
   * Contains already loaded cache invalidations from the database.
   *
   * @var array
   */
  protected $tagCache = array();

  /**
   * A list of tags that have already been invalidated in this request.
   *
   * Used to prevent the invalidation of the same cache tag multiple times.
   *
   * @var array
   */
  protected $invalidatedTags = array();

  /**
   * Constructs a CacheCacheTagsChecksum object.
   */
  public function __construct(CacheRawFactoryInterface $cache) {
    $this->backend = $cache->get('supercache_tags');
  }

  /**
   * {@inheritdoc}
   */
  public function invalidateTags(array $tags) {
    $tags_to_invalidate = [];
    foreach ($tags as $tag) {
      // Only invalidate tags once per request unless they are written again.
      if (isset($this->invalidatedTags[$tag])) {
        unset($tags[$tag]);
        continue;
      }
      $this->invalidatedTags[$tag] = TRUE;
      unset($this->tagCache[$tag]);
      $tags_to_invalidate[] = $tag;
    }
    // Some cache backends are very efficient at doing
    // batch counters in a single statement.
    $this->backend->counterMultiple($tags_to_invalidate, 1);
  }

  /**
   * {@inheritdoc}
   */
  public function getCurrentChecksum(array $tags) {
    // Remove tags that were already invalidated during this request from the
    // static caches so that another invalidation can occur later in the same
    // request. Without that, written cache items would not be invalidated
    // correctly.
    foreach ($tags as $tag) {
      unset($this->invalidatedTags[$tag]);
    }
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
    $query_tags = array_diff($tags, array_keys($this->tagCache));
    if ($query_tags) {
      $db_tags = array();
      $items = $this->backend->counterGetMultiple($query_tags);
      foreach($items as $cid => $data) {
        $db_tags[$cid] = $data;
      }

      // If we could not retrieve a tag (because it has never been invalidated) make
      // sure that we initialize it, otherwise the storage backend will keep looking
      // for it once again and again in the persistent backend when using FastChained
      // as the storage.
      $missing_tags = array_diff($query_tags, array_keys($db_tags));
      foreach ($missing_tags as $tag) {
        // Set to the 0 reference value (which is not the initial!)
        $this->backend->counterSet($tag, 0);
      }

      $this->tagCache += $db_tags;

      // Fill static cache with empty objects for tags not found in the database.
      $this->tagCache += array_fill_keys($missing_tags, 0);
    }

    $real_tags = array_intersect_key($this->tagCache, array_flip($tags));
    return array_sum($real_tags);
  }

  /**
   * {@inheritdoc}
   */
  public function reset() {
    $this->tagCache = array();
    $this->invalidatedTags = array();
  }

  /**
   * {@inheritdoc}
   */
  public function resetTags() {
    $this->reset();
    $this->backend->deleteAll();
  }
}
