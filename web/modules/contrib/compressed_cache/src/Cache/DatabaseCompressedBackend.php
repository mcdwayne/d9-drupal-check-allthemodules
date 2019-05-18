<?php

namespace Drupal\compressed_cache\Cache;

use Drupal\Component\Assertion\Inspector;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\CacheTagsChecksumInterface;
use Drupal\Core\Cache\DatabaseBackend;
use Drupal\Core\Database\Connection;

/**
 * Defines a default cache implementation.
 *
 * This is Drupal's default cache implementation. It uses the database to store
 * cached data. Each cache bin corresponds to a database table by the same name.
 *
 * @ingroup cache
 */
class DatabaseCompressedBackend extends DatabaseBackend {

  const SERIALIZED_COMPRESSED = 2;
  const STRING_COMPRESSED = 3;

  /**
   * True if gzip functions are available.
   *
   * @var bool
   */
  protected $gzipAvailable;

  /**
   * Redis module claims level 1 provides good enough results.
   *
   * @var int
   * @see https://cgit.drupalcode.org/redis/tree/lib/Redis/CacheCompressed.php?h=7.x-3.x#n24
   * @see https://www.drupal.org/project/redis/issues/2826332#comment-11794927
   */
  protected $cacheCompressionRatio;

  /**
   * Seems to be completely based on gut feeling. can not find any sources googling this topic.
   *
   * @var int
   */
  protected $cacheCompressionSizeThreshold;

  /**
   * Whether garbage collection is enabled or not.
   * @var bool
   */
  protected $garbageCollectionEnabled = TRUE;

  /**
   * Constructs a DatabaseBackend object.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param \Drupal\Core\Cache\CacheTagsChecksumInterface $checksum_provider
   *   The cache tags checksum provider.
   * @param string $bin
   *   The cache bin for which the object is created.
   * @param int $max_rows
   *   (optional) The maximum number of rows that are allowed in this cache bin
   *   table.
   * @param int $cache_compression_ratio
   *   (optional) cache commpression level
   * @param int $cache_compression_size_threshold
   *   (optional) Minimum string length to enable compression.
   * @param bool $garbage_collection_enabled
   *   (optional) Whether garbage collection is enabled or not.
   */
  public function __construct(Connection $connection, CacheTagsChecksumInterface $checksum_provider, $bin, $max_rows = NULL, $cache_compression_ratio = 6, $cache_compression_size_threshold = 100, $garbage_collection_enabled = TRUE) {
    parent::__construct($connection, $checksum_provider, $bin, $max_rows);

    $this->cacheCompressionRatio = $cache_compression_ratio;
    $this->cacheCompressionSizeThreshold = $cache_compression_size_threshold;
    $this->garbageCollectionEnabled = $garbage_collection_enabled;

    // Check if gzip compression is available.
    $this->gzipAvailable = (function_exists('gzcompress') && function_exists('gzuncompress'));
  }

  /**
   * Prepares a cached item.
   *
   * Checks that items are either permanent or did not expire, and unserializes
   * data as appropriate.
   *
   * @param object $cache
   *   An item loaded from cache_get() or cache_get_multiple().
   * @param bool $allow_invalid
   *   If FALSE, the method returns FALSE if the cache item is not valid.
   *
   * @return mixed|false
   *   The item with data unserialized as appropriate and a property indicating
   *   whether the item is valid, or FALSE if there is no valid item to load.
   */
  protected function prepareItem($cache, $allow_invalid) {
    if (!isset($cache->data)) {
      return FALSE;
    }

    $cache->tags = $cache->tags ? explode(' ', $cache->tags) : [];

    // Check expire time.
    $cache->valid = $cache->expire == Cache::PERMANENT || $cache->expire >= REQUEST_TIME;

    // Check if invalidateTags() has been called with any of the items's tags.
    if (!$this->checksumProvider->isValid($cache->checksum, $cache->tags)) {
      $cache->valid = FALSE;
    }

    if (!$allow_invalid && !$cache->valid) {
      return FALSE;
    }

    // Unserialize and return the cached data.
    if ($cache->serialized) {
      switch ($cache->serialized) {
        case self::SERIALIZED_COMPRESSED:
          // Decompress.
          if ($this->gzipAvailable) {
            $cache->data = unserialize(gzuncompress($cache->data));
          }
          else {
            // No gzip available. unusable cache.
            return FALSE;
          }
          break;

        case self::STRING_COMPRESSED:
          // Decompress.
          if ($this->gzipAvailable) {
            $cache->data = gzuncompress($cache->data);
          }
          else {
            // No gzip available. unusable cache.
            return FALSE;
          }
          break;

        default:
          // Fallback, uncompressed serialized data (1)
          $cache->data = unserialize($cache->data);
          break;
      }
    }

    return $cache;
  }

  /**
   * Stores multiple items in the persistent cache.
   *
   * @param array $items
   *   An array of cache items, keyed by cid.
   *
   * @see \Drupal\Core\Cache\CacheBackendInterface::setMultiple()
   */
  protected function doSetMultiple(array $items) {
    $values = [];

    foreach ($items as $cid => $item) {
      $item += [
        'expire' => CacheBackendInterface::CACHE_PERMANENT,
        'tags' => [],
      ];

      assert(Inspector::assertAllStrings($item['tags']), 'Cache Tags must be strings.');
      $item['tags'] = array_unique($item['tags']);
      // Sort the cache tags so that they are stored consistently in the DB.
      sort($item['tags']);

      $fields = [
        'cid' => $this->normalizeCid($cid),
        'expire' => $item['expire'],
        'created' => round(microtime(TRUE), 3),
        'tags' => implode(' ', $item['tags']),
        'checksum' => $this->checksumProvider->getCurrentChecksum($item['tags']),
      ];

      if (!is_string($item['data'])) {
        $fields['data'] = serialize($item['data']);
        $fields['serialized'] = 1;
      }
      else {
        $fields['data'] = $item['data'];
        $fields['serialized'] = 0;
      }

      // Add compression.
      $data_length = strlen($fields['data']);
      if ($this->gzipAvailable && $data_length > $this->cacheCompressionSizeThreshold) {
        $compressed_data = gzcompress($fields['data'], $this->cacheCompressionRatio);
        // Check if compressed string is shorter than original.
        if ($compressed_data && strlen($compressed_data) < $data_length) {
          $fields['data'] = $compressed_data;
          if ($fields['serialized'] == 1) {
            // Serialized object, set state accordingly.
            $fields['serialized'] = self::SERIALIZED_COMPRESSED;
          }
          else {
            // Usual string.
            $fields['serialized'] = self::STRING_COMPRESSED;
          }
        }
      }

      $values[] = $fields;
    }

    // Use an upsert query which is atomic and optimized for multiple-row
    // merges.
    $query = $this->connection
      ->upsert($this->bin)
      ->key('cid')
      ->fields(['cid', 'expire', 'created', 'tags', 'checksum', 'data', 'serialized']);
    foreach ($values as $fields) {
      // Only pass the values since the order of $fields matches the order of
      // the insert fields. This is a performance optimization to avoid
      // unnecessary loops within the method.
      $query->values(array_values($fields));
    }

    $query->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function garbageCollection() {
    if ($this->garbageCollectionEnabled) {
      parent::garbageCollection();
    }
  }

}
