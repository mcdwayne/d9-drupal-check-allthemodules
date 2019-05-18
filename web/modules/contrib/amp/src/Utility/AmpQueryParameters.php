<?php

namespace Drupal\amp\Utility;

use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Class AmpQueryParameters
 *
 * Adds amp query parameters to a URL.
 *
 * @package Drupal\amp\Utility
 */
class AmpQueryParameters extends ServiceProviderBase {

  /**
   * Add amp query parameter to a URL.
   *
   * @param string $url
   *   The original URL value.
   * @param boolean $development
   *   Option to append development to the end of the URL.
   *
   * @return string
   *   A url containing the additional amp query parameter(s).
   */
  public function add($url, $development = FALSE) {
    // Append amp query string parameter
    if (strpos($url, '?') === FALSE) {
      $amp_url = $url . "?amp";
    }
    else {
      $amp_url = $url . "&amp";
    }

    // Append optional development query string parameter.
    if ($development) {
      $amp_url = $amp_url . "&debug#development=1";
    }

    return $amp_url;
  }
}
