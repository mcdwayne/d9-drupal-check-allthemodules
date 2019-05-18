<?php
/**
 * @file
 * Contains \Drupal\live_weather\LiveWeatherInterface.
 */

namespace Drupal\live_weather;

/**
 * Live WeatherInterface.
 */
interface LiveWeatherInterface {

  /**
   * Get location data.
   */
  public function locationCheck($woeid = NULL, $filter = '', $unit = 'f');
  /**
   * Check Day or Night.
   */
  public static function checkDayNight($date, $sunrise, $sunset);
  /**
   * Get Wind Direction.
   */
  public static function windDirection($direction);
  /**
   * buildBaseString.
   */
  public static function buildBaseString($baseURI, $method, $params);
  /**
   * buildAuthorizationHeader.
   */
  public static function buildAuthorizationHeader($oauth);

}
