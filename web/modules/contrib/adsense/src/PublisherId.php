<?php

namespace Drupal\adsense;

/**
 * Class PublisherId.
 */
class PublisherId {

  /**
   * Returns the site's publisher ID.
   *
   * @return string
   *   The configured site's publisher ID.
   */
  public static function get() {
    return \Drupal::config('adsense.settings')->get('adsense_basic_id');
  }

}
