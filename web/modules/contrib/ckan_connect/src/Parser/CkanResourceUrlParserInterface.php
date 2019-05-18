<?php

namespace Drupal\ckan_connect\Parser;

/**
 * Provides an interface defining a CKAN resource URL parser.
 */
interface CkanResourceUrlParserInterface {

  /**
   * Parses a CKAN resource URL.
   *
   * @param string $url
   *   The URL.
   *
   * @return array|bool
   *   An array of options or FALSE if URL cannot be parsed.
   */
  public function parse($url);

  /**
   * Gets the package ID.
   *
   * @param string $url
   *   The URL.
   *
   * @return string
   *   A package ID.
   */
  public function getPackageId($url);

  /**
   * Gets the resource ID.
   *
   * @param string $url
   *   The URL.
   *
   * @return string
   *   A resource ID.
   */
  public function getResourceId($url);

  /**
   * Gets the resource view ID.
   *
   * @param string $url
   *   The URL.
   *
   * @return string
   *   A resource view ID.
   */
  public function getResourceViewId($url);

  /**
   * Gets the resource view URL.
   *
   * @param string $url
   *   The URL.
   *
   * @return string
   *   A resource view URL.
   */
  public function getResourceViewUrl($url);

}
