<?php

/**
 * @file
 * Contains \Drupal\media_download_all\Cache\MDACacheTagsInvalidator.
 */

namespace Drupal\media_download_all\Cache;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;

/**
 * Cache invalidator to clean up "download all" archives when caches are cleared.
 */
class MDACacheTagsInvalidator implements CacheTagsInvalidatorInterface
{
  public function invalidateTags(array $tags)
  {
    $entity_types = array_keys(\Drupal::entityTypeManager()->getDefinitions());
    foreach ($tags AS $tag) {
      $tag_parts = explode(':', $tag);
      // Look for a cache tag in the form <entity_type>:<entity_id>
      if (in_array($tag_parts[0], $entity_types) && is_numeric($tag_parts[1])) {
        $cid = 'media_download_all:' . $tag_parts[0] . ':' . $tag_parts[1];
        $cache = \Drupal::cache()->get($cid);
        if ($cache) {
          $cached_files = $cache->data;
          if (!empty($cached_files)) {
            foreach ($cached_files AS $file) {
              unlink($file);
            }
          }
          \Drupal::cache()->delete($cid);
        }
        break;
      }
    }
  }
}