<?php

namespace Drupal\price;

/**
 * Holds a reference to the current country, resolved on demand.
 *
 * @see \Drupal\price\CurrentCountry
 */
interface CurrentCountryInterface {

  /**
   * Gets the country for the current request.
   *
   * @return \Drupal\price\Country
   *   The country.
   */
  public function getCountry();

}
