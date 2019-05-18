<?php

/**
 * @file
 * Contains \Drupal\couchbasedrupal\Cache\CouchbaseTagsChecksum.
 */

namespace Drupal\couchbasedrupal\Cache;

use Drupal\Core\Cache\CacheTagsChecksumInterface;
use Drupal\Core\Site\Settings;

use Drupal\supercache\Cache\CacheTagsInvalidatorInterface;

use Drupal\couchbasedrupal\CouchbaseManager;
use Drupal\couchbasedrupal\CouchbaseBucket as Bucket;

/**
 * Cache tags invalidations checksum implementation that uses the database.
 *
 * Only use this if running on a single web head setup!
 *
 */
class CouchbaseTagsChecksum implements CacheTagsChecksumInterface, CacheTagsInvalidatorInterface {

  /**
   * The prefix for this site.
   * 
   * @var string
   */
  protected $sitePrefix;

  /**
   * Prefix used to store the tag keys.
   * 
   * @var string
   */
  protected $tagPrefix;

  /**
   * Bucket.
   *
   * @var Bucket
   */
  protected $bucket;

  /**
   * Couchbase manager.
   *
   * @var CouchbaseManager
   */
  protected $couchbaseManager;

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
   * @var string[]
   */
  protected $invalidatedTags = array();

  /**
   * Retrieve the active bucket.
   * 
   * @return Bucket
   */
  protected function getBucket() {
    if (empty($this->bucket)) {
      $this->bucket = $this->couchbaseManager->getBucketFromConfig('default');
    }
    return $this->bucket;
  }

  /**
   * Constructs a CouchbaseTagsChecksum object.
   * @param string $root
   *   The prefix for this site.
   * @param string $root
   *   The site's path.
   * @param CouchbaseManager $manager
   *   A couchbase server manager.
   */
  public function __construct(CouchbaseManager $manager) {
    $this->sitePrefix = $manager->getSitePrefix();
    $this->tagPrefix = $this->sitePrefix . '::cache_tag::';
    $this->couchbaseManager = $manager;
  }

  /**
   * {@inheritdoc}
   */
  public function invalidateTags(array $tags) {
    $keys_to_increment = array();
    foreach ($tags as $tag) {
      // Only invalidate tags once per request unless they are written again.
      if (isset($this->invalidatedTags[$tag])) {
        continue;
      }
      $keys_to_increment[] = $tag;
      $this->invalidatedTags[$tag] = TRUE;
      unset($this->tagCache[$tag]);
    }
    // We can increment all of them on the storage at once.
    $keys_to_increment = preg_filter('/^/', $this->tagPrefix, $keys_to_increment);
    $this->getBucket()->counter($keys_to_increment, 1, array('initial' => 1));
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
    $missing_tags = array_diff($tags, array_keys($this->tagCache));

    if ($missing_tags) {
      $keys = preg_filter('/^/', $this->tagPrefix, $tags);
      $stored_tags = $this->getBucket()->getMultiple($keys);
      $key_map = array_combine($keys, $tags);
      foreach ($stored_tags as $key => $data) {
        if (empty($data->error)) {
          $this->tagCache[$key_map[$key]] = (int) $data->value;
        }
      }
      // Fill static cache with empty objects for tags not found in the database.
      $this->tagCache += array_fill_keys(array_diff($missing_tags, array_keys($stored_tags)), 0);
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
    $this->getBucket()->deleteAllByPrefix($this->tagPrefix);
  }
}
