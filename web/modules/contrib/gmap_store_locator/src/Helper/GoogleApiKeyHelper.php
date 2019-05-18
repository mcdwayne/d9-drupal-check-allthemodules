<?php

namespace Drupal\store_locator\Helper;

/**
 * Class GoogleApiKeyHelper.
 *
 * @package Drupal\store_locator\Helper
 */
class GoogleApiKeyHelper {

  /**
   * Get the Google Map API Key.
   *
   * @return string
   *   Google Map API Key.
   */
  public static function getGoogleApiKey() {
    $key = \Drupal::config('store_locator.settings')->get('api_key');
    $mapKey = [];
    $mapKey = [
      '#tag' => 'script',
      '#attributes' => ['src' => '//maps.googleapis.com/maps/api/js?key=' . $key],
    ];
    return $mapKey;
  }

}
