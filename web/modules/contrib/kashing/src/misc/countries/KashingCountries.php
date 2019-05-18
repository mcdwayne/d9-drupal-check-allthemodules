<?php

namespace Drupal\kashing\misc\countries;

/**
 * Kashing countries class.
 */
class KashingCountries {
  private $countries;
  private $countriesDataPath;

  /**
   * Constructor class.
   */
  public function __construct() {
    $module_handler = \Drupal::service('module_handler');
    $module_path = $module_handler->getModule('kashing')->getPath();

    $this->countriesDataPath = $module_path . '/src/misc/countries/';
    $this->initCountries();
  }

  /**
   * Get country name by ISO 3166-1 Code.
   *
   * @param string $code
   *   Country ISO code.
   *
   * @return string
   *   Country name
   */
  public function getName($code) {
    if (array_key_exists($code, $this->countries)) {
      return $this->countries[$code];
    }
    // Country does not have a symbol.
    return NULL;
  }

  /**
   * Get all countries.
   *
   * @return array
   *   countries array
   */
  public function getAll() {
    return $this->countries;
  }

  /**
   * Assign countries array to the countries variable.
   */
  public function initCountries() {
    // A full list of countries.
    $file = $this->countriesDataPath . 'countries-list.php';
    if (is_file($file)) {
      $countries_list_array = include $file;
      $new_countries_array = [];
      if (is_array($countries_list_array)) {
        foreach ($countries_list_array as $code => $name) {
          $new_countries_array[$code] = $name;
        }
        $this->countries = $new_countries_array;
      }
    }
  }

}
