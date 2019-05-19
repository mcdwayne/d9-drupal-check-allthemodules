<?php

/**
 * @file
 * Definition of ServiceInterface.
 */

namespace WoW\Core;

/**
 * Service performs GET operations against battle.net API.
 */
interface ServiceInterface {

  /**
   * Creates a new request.
   *
   * @param string $path
   *   Resource URL being linked to.
   *
   * @return Request
   *   A Request object.
   */
  public function newRequest($path);

  /**
   * Returns an API compliant locale value for this service.
   *
   * @param string $language
   *   The language used to determine the locale.
   *
   * @return string
   *   An API compliant locale or NULL.
   */
  public function getLocale($language);

  /**
   * Returns a list of locales supported by this service.
   *
   * All of the API resources provided adhere to the practice of providing
   * localized strings using the locale query string parameter. The locales
   * supported vary from region to region and align with those supported on the
   * community sites.
   *
   * @return array
   *   An array of available locales keyed by language code for this region.
   */
  public function getLocales();

  /**
   * Returns the API region for this service.
   *
   * @return string
   *   The host for this service.
   */
  public function getRegion();

  /**
   * Performs an HTTP GET request.
   *
   * @param string $path
   *   Resource URL being linked to. It is the responsibility of the caller to
   *   url encode the path: http://$host/api/wow/$path.
   * @param array $query
   *   (Optional) An array of query key/value-pairs (without any URL-encoding)
   *   to append to the URL.
   *   - locale: You can specify your own locale here.
   *     It it the responsibility of the caller to pass a valid locale.
   *     Default to the global $language_content->language.
   *     @see wow_api_locale()
   * @param array $headers
   *   (Optional) An array containing request headers to send as name/value
   *   pairs. It is the responsibility of the caller to time stamp the request
   *   by passing a 'Date' header in the following format: 'D, d M Y H:i:s T'.
   *
   *  @return Response
   *    The Service response in the form of a Response object.
   */
  public function request($path, array $query = array(), array $headers = array());

}
