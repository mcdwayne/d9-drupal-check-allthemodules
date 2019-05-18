<?php

namespace Drupal\drupal_inquicker\Source;

use Drupal\drupal_inquicker\Utilities\Collection;

/**
 * Collection of Source objects.
 */
class SourceCollection extends Collection {

  /**
   * Get a source by key.
   *
   * @param string $key
   *   A key such as dummy or default.
   *
   * @return Source
   *   A Source object.
   */
  public function findByKey(string $key) : Source {
    foreach ($this as $source) {
      if ($source->key() == $key) {
        return $source;
      }
    }
    return new InvalidSource($key, [], $this->t('No source exists with key @k', [
      '@k' => $key,
    ]));
  }

  /**
   * {@inheritdoc}
   */
  public function itemClass() : string {
    return Source::class;
  }

  /**
   * Get only live sources.
   *
   * @return SourceCollection
   *   Only live sources.
   */
  public function liveOnly() : SourceCollection {
    return $this->filter(new SourceCollection(), function ($source) {
      return $source->live();
    });
  }

  /**
   * Get only valid sources.
   *
   * @return SourceCollection
   *   Only valid sources.
   */
  public function validOnly() : SourceCollection {
    return $this->filter(new SourceCollection(), function ($source) {
      return $source->valid();
    });
  }

  /**
   * Get only invalid sources.
   *
   * @return SourceCollection
   *   Only invalid sources.
   */
  public function invalidOnly() : SourceCollection {
    return $this->filter(new SourceCollection(), function ($source) {
      return !$source->valid();
    });
  }

}
