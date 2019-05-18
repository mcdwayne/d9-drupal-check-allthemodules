<?php

namespace Drupal\prefetcher\Entity;

use Drupal\Core\Entity\ContentEntityType;

/**
 * Class PrefetcherUriType
 *
 * This type class disables caching for prefetcher uris.
 * Other content entities like nodes or terms are more important to be cached
 * than prefetcher uris inside eventual LRU-based cache backends.
 */
class PrefetcherUriType extends ContentEntityType {

  /**
   * {@inheritdoc}
   */
  protected $persistent_cache = FALSE;

  /**
   * {@inheritdoc}
   */
  public function isPersistentlyCacheable() {
    return $this->persistent_cache;
  }

}
