<?php

/**
 * @file
 * Contains \Drupal\smart_ip\WebServiceUtilityInterface.
 */

namespace Drupal\smart_ip;

/**
 * Provides an interface for Smart IP's data source modules for its web service.
 *
 * @package Drupal\smart_ip
 */
interface WebServiceUtilityInterface {

  /**
   * Get Smart IP's data source module's web service URL.
   *
   * @param string $ipAddress
   *   IP address to query for geolocation.
   * @return string
   *   Smart IP's data source module's web service URL.
   */
  public static function getUrl($ipAddress);

  /**
   * Perform HTTP request to the Smart IP's data source module web service.
   *
   * @param string $url
   *   URL provided by Smart IP's data source module web service for geolocation
   *   query.
   * @return string
   *   Raw Geolocation data returned by Smart IP's data source module web
   *   service.
   */
  public static function sendRequest($url);

  /**
   * Perform HTTP request and decoding the raw Geolocation data returned by
   * Smart IP's data source module web service.
   *
   * @param string $ipAddress
   *   IP address to query for geolocation.
   * @return array
   *   Geolocation data returned by Smart IP's data source module web service.
   */
  public static function getGeolocation($ipAddress);

}
