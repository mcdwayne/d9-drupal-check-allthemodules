<?php

namespace Drupal\flickr\Service;

use Drupal\flickr_api\Service\Helpers as FlickrApiHelpers;

/**
 * Class Helpers.
 *
 * @package Drupal\flickr\Service
 */
class Helpers {

  /**
   * Helpers constructor.
   *
   * @param \Drupal\flickr_api\Service\Helpers $flickrApiHelpers
   *   Helpers.
   */
  public function __construct(FlickrApiHelpers $flickrApiHelpers) {
    // Flickr API Helpers.
    $this->flickrApiHelpers = $flickrApiHelpers;
  }

  /**
   * Split the config.
   *
   * Parse parameters to the fiter from a format like:
   * id=26159919@N00, size=m,num=9
   * into an associative array with two sub-arrays. The first sub-array are
   * parameters for the request,
   * the second are HTML attributes (class and style).
   *
   * @param string $string
   *   Param String.
   *
   * @return array
   *   Return array.
   */
  public function splitConfig($string) {
    $config = [];
    $attribs = [];

    // Put each setting on its own line.
    $string = str_replace(',', "\n", $string);

    // Break them up around the equal sign (=).
    preg_match_all('/([a-zA-Z_.]+)=([-@\/0-9a-zA-Z :;_.\|\%"\'&°]+)/', $string, $parts, PREG_SET_ORDER);

    foreach ($parts as $part) {
      // Normalize to lowercase and remove extra spaces.
      $name = strtolower(trim($part[1]));
      $value = htmlspecialchars_decode(trim($part[2]));

      // Remove undesired but tolerated characters from the value.
      $value = str_replace(str_split('"\''), '', $value);

      if ($name == 'style' || $name == 'class') {
        $attribs[$name] = $value;
      }
      else {
        $config[$name] = $value;
      }
    }

    return [$config, $attribs];
  }

}
