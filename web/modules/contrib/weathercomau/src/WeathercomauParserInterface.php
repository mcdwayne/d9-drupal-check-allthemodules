<?php

/**
 * @file
 * Contains \Drupal\weathercomau\WeathercomauParserInterface.
 */

namespace Drupal\weathercomau;

interface WeathercomauParserInterface {

  /**
   * Parses the XML of the Weather.com.au data.
   *
   * @param string $raw_xml
   *   A raw XML string Weather.com.au data.
   *
   * @return array
   *   Array of parsed Weather.com.au data, or NULL if there was an error
   *   parsing the string.
   */
  public function parse($raw_xml);

}
