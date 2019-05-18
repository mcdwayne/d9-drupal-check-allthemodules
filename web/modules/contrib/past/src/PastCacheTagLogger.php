<?php

namespace Drupal\past;

use Drupal\Core\Cache\CacheTagsInvalidatorInterface;

/**
 * A cache tags invalidator that logs to past.
 */
class PastCacheTagLogger Implements CacheTagsInvalidatorInterface {

  /**
   * {@inheritdoc}
   */
  public function invalidateTags(array $tags) {
    if (\Drupal::config('past.settings')->get('log_cache_tags')) {

      // Exclude certain tags, as they are not relevant for this.
      $filtered_tags = $this->filterTags($tags);
      if (empty($filtered_tags)) {
        return;
      }

      $event = past_event_create('past', 'cache_tag_invalidation', 'Invalidation for: ' . implode(', ', $filtered_tags));
      $event->addArgument('tags', $filtered_tags);
      $event->addArgument('backtrace', _past_get_formatted_backtrace(NULL, 3));
      $event->save();
    }
  }

  /**
   * Filters cache tags based on a blacklist.
   *
   * @param array $tags
   *   List of tags to filter.
   *
   * @return array
   *   List of tags excluding blacklisted cache tags.
   */
  protected function filterTags(array $tags) {
    $blacklist = ['past_event_list', 'configFindByPrefix'];
    // @todo Support wildcards.
    return array_diff($tags, $blacklist);
  }

}
