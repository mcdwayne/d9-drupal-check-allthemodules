<?php

/**
 * @file
 * Contains \Drupal\smart_ip\SmartIpLocationInterface.
 */

namespace Drupal\smart_ip;

/**
 * Provides an interface for Smart IP's data location.
 *
 * @package Drupal\smart_ip
 */
interface SmartIpLocationInterface {

  /**
   * Source ID for pure Smart IP as geolocation source.
   */
  const SMART_IP = 'smart_ip';

  /**
   * Source ID for Google Map Geocoded Smart IP as geolocation source.
   */
  const GEOCODED_SMART_IP = 'geocoded_smart_ip';

  /**
   * Source ID for W3C as geolocation source.
   */
  const W3C = 'w3c';

  /**
   * Sets the Smart IP location data..
   *
   * @param array $location
   *   An array of Smart IP location data.
   * @return \Drupal\smart_ip\SmartIpLocationInterface
   *   Smart IP's data location.
   */
  public function setData(array $location);

  /**
   * Sets an item in Smart IP location data.
   *
   * @param string $key
   *   Name of the item in Smart IP location data.
   * @param mixed $value
   *   Value of the item of interest.
   * @return \Drupal\smart_ip\SmartIpLocationInterface
   *   Smart IP's data location.
   */
  public function set($key, $value);

  /**
   * Gets all the Smart IP location data.
   *
   * @param bool $update
   *   Flag to execute update user's geolocation if it is still empty. Default
   *   to TRUE.
   * @return array
   *   An array of Smart IP location data.
   */
  public function getData($update = TRUE);

  /**
   * Gets an item in Smart IP location data or all the Smart IP location data
   * if supplied no parameter.
   *
   * @param string $key
   *   Name of the item in Smart IP location data.
   * @return mixed
   *   Value of the requested item in Smart IP location data or an array of it.
   */
  public function get($key);

  /**
   * Saves the Smart IP location data to user data and session (for anonymous,
   * saves to session only).
   *
   * @return \Drupal\smart_ip\SmartIpLocationInterface
   *   Smart IP's data location.
   */
  public function save();

  /**
   * Deletes the Smart IP location data in user data and session.
   *
   * @return \Drupal\smart_ip\SmartIpLocationInterface
   *   Smart IP's data location.
   */
  public function delete();

}
