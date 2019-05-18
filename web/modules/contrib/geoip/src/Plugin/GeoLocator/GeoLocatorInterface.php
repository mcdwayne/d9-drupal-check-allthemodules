<?php

namespace Drupal\geoip\Plugin\GeoLocator;

/**
 * Interface GeoLocatorInterface.
 */
interface GeoLocatorInterface {

  /**
   * Get the plugin's ID.
   *
   * @return string
   *   The geolocator ID
   */
  public function getId();

  /**
   * Get the plugin's label.
   *
   * @return string
   *   The geolocator label
   */
  public function getLabel();

  /**
   * Get the plugin's description.
   *
   * @return string
   *   The geolocator description
   */
  public function getDescription();

  /**
   * Performs geolocation on an address.
   *
   * @param string $ip_address
   *   The IP address to geolocate.
   *
   * @return string
   *   The geolocated country code, or NULL if not found.
   */
  public function geolocate($ip_address);

}
