<?php

/**
 * @file
 * Contains \Drupal\weathercomau\WeathercomauReaderInterface.
 */

namespace Drupal\weathercomau;

interface WeathercomauReaderInterface {

  /**
   * Reads the Weather.com.au data.
   *
   * @param string $city
   *   The city.
   * @param string $state
   *   The state.
   *
   * @return array
   *   Array of Weather.com.au data.
   */
  public function read($city, $state);

}
