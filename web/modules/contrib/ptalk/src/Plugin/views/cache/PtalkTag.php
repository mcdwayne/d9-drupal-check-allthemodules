<?php

namespace Drupal\ptalk\Plugin\views\cache;

use Drupal\Core\Cache\Cache;
use Drupal\views\Plugin\views\cache\Tag;

/**
 * Simple caching of query results for Views displays.
 *
 * @ingroup views_cache_plugins
 *
 * @ViewsCache(
 *   id = "ptalk_tag",
 *   title = @Translation("Ptalk tag based"),
 *   help = @Translation("Tag based caching of data. Caches will persist until any related cache tags are invalidated.")
 * )
 */
class PtalkTag extends Tag {

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    $tags = parent::getCacheTags();
    $tags = array_diff($tags, array('ptalk_thread_list', 'ptalk_message_list'));

    return Cache::mergeTags(array('ptalk_participant:' . \Drupal::currentUser()->id()), $tags);
  }

}
