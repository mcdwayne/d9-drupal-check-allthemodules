<?php

namespace Drupal\leaflet_countries;

/**
 * Class Countries
 * @package Drupal\leaflet_countries
 *
 * Contains helper methods for accessing GeoJSON library data
 * and processing the data with GeoPHP.
 */
class Countries {

  /**
   * Loads a specific file from the countries library and returns the JSON.
   *
   * @param string $path
   *   The name of the file to load.
   *
   * @return string
   *   The JSON string contained in the loaded file.
   */
  protected static function getJSONContent($path) {
    $filepath = 'libraries/countries/' . $path;
    $realpath = \Drupal::service('file_system')->realpath($filepath);
    $file = file_get_contents($realpath);
    return $file;
  }

  /**
   * Loads the countries JSON file from the filesystem.
   *
   * @return string
   *   The contents of the JSON file.
   */
  protected static function getCountriesJSON() {
    return self::getJSONContent('countries.json');
  }

  /**
   * Decodes the countries JSON data and returns it.
   */
  public static function getCountries() {
    $countries = json_decode(self::getCountriesJSON());
    return $countries;
  }

  /**
   * Gets the country codes and returns an array of them.
   *
   * @return array
   *   A simple array of country codes.
   */
  public static function getCodes() {
    $list = self::getCodesAndLabels();
    $codes = array();
    foreach($list as $code => $name) {
      $codes[] = $code;
    }
    return $codes;
  }

  /**
   * Gets the country codes and labels and returns a keyed value pair.
   *
   * @return array
   *   An array with the key being the country code and the value being
   *   the country's common name.
   */
  public static function getCodesAndLabels() {
    $list = self::getCountries();
    $labels = array();
    foreach($list as $item) {
      $labels[$item->cca3] = $item->name->common;
    }
    asort($labels);
    return $labels;
  }

  /**
   * Gets an individual country's JSON data.
   *
   * @param $code
   *   The country code of the country whose data should be returned.
   *
   * @return $string
   *   The JSON string for the country.
   */
  public static function getIndividualCountryJSON($code) {
    $filepath = 'data/' . strtolower($code) . '.topo.json';
    return self::getJSONContent($filepath);
  }

}
