<?php

namespace Drupal\adsense;

/**
 * Class ContentAdBase.
 */
abstract class ContentAdBase extends AdsenseAdBase {
  /**
   * Ad Format.
   *
   * @var string
   */
  protected $format;

  /**
   * Provides the width and height of the ad.
   *
   * @param string $format
   *   Format of the ad (usually WIDTHxHEIGHT).
   *
   * @return array|null
   *   Array with dimensions of the ad, or null if format is not a
   */
  public static function dimensions($format) {
    if (preg_match('!^(\d+)x(\d+)(?:_5)?$!', $format, $matches)) {
      return [$matches[1], $matches[2]];
    }
    return NULL;
  }

  /**
   * Gets the ad format.
   *
   * @return string
   *   This ad's format.
   */
  public function getFormat() {
    return $this->format;
  }

}
