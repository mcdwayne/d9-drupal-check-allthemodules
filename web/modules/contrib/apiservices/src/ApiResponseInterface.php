<?php

/**
 * @file
 * Contains \Drupal\apiservices\ApiResponseInterface.
 */

namespace Drupal\apiservices;

/**
 * Defines an interface for immutable API responses.
 */
interface ApiResponseInterface {

  /**
   * Gets the response body.
   *
   * @return string
   *   A string containing the body of the HTTP response.
   */
  public function getBody();

  /**
   * Gets a response header.
   *
   * @param string $header
   *   A HTTP header field, such as 'Date' or 'User-Agent'.
   *
   * @return string|FALSE
   *   The value of a header field or FALSE if the specified header was not
   *   contained in the response.
   */
  public function getHeader($header);

  /**
   * Gets a list of all headers in the response.
   *
   * @return array
   *   An array containing all headers contained in the HTTP response.
   */
  public function getHeaders();

  /**
   * Gets the HTTP protocol version of the response.
   *
   * @return string
   *   An protocol version used by the HTTP response (ex: "1.0" or "1.1").
   */
  public function getProtocol();

  /**
   * Gets the HTTP reason phrase.
   *
   * @return string
   *   The common reason associated with an HTTP status code.
   */
  public function getReason();

  /**
   * Gets the HTTP status code.
   *
   * @return int
   *   The status code of the HTTP response.
   */
  public function getStatusCode();

  /**
   * Create a copy of the response with the specified headers.
   *
   * @param array $headers
   *   An array of header names and values.
   *
   * @return static
   *   An API response object with the given headers.
   */
  public function withHeaders(array $headers);

}
