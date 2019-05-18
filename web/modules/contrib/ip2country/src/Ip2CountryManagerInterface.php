<?php

namespace Drupal\ip2country;

/**
 * Interface for Ip2CountryManager.
 */
interface Ip2CountryManagerInterface {

  /**
   * Gets the ISO 3166 2-character country code from the IP address.
   *
   * @param string|int|null $ip_address
   *   IP address either as a dotted quad string (e.g. "127.0.0.1") or
   *   as a 32-bit unsigned long integer.
   *
   * @return string|false
   *   ISO 3166-1 2-character country code for this IP address, or
   *   FALSE if the lookup failed to find a country.
   */
  public function getCountry($ip_address = NULL);

}
