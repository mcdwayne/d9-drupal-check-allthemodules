<?php

/**
 * @file
 * Contains \Drupal\weathercomau\WeathercomauFetcherInterface.
 */

namespace Drupal\weathercomau;

interface WeathercomauFetcherInterface {

  /**
   * Returns the base of the URL to fetch Weather.com.au data.
   *
   * @return string
   *   The base of the URL to fetch Weather.com.au data.
   */
  public function getFetchBaseUrl();

  /**
   * Generates the URL to fetch Weather.com.au data.
   *
   * @param string $city
   *   The city.
   * @param string $state
   *   The state.
   *
   * @return string
   *   The URL to fetch Weather.com.au data.
   */
  public function buildFetchUrl($city, $state);

  /**
   * Retrieves the Weather.com.au data.
   *
   * @param string $city
   *   The city.
   * @param string $state
   *   The state.
   *
   * @return string
   *   The Weather.com.au data.
   */
  public function fetch($city, $state);

}
