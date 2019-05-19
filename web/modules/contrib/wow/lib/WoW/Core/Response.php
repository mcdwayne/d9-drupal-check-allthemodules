<?php

/**
 * @file
 * Definition of Response.
 */

namespace WoW\Core;

/**
 * Defines the Response class.
 */
class Response {

  /**
   * An integer containing the response status code, or the error code if an
   * error occurred.
   *
   * @var int
   */
  protected $code;

  /**
   * The response protocol (e.g. HTTP/1.1 or HTTP/1.0).
   *
   * @param string
   */
  protected $protocol;

  /**
   * A string containing the request body that was sent.
   *
   * @var string
   */
  protected $request;

  /**
   * An array containing the response headers as name/value pairs. HTTP header
   * names are case-insensitive (RFC 2616, section 4.2), so for easy access the
   * array keys are returned in lower case.
   *
   * @var array
   */
  protected $headers;

  /**
   * The data returned by the service.
   *
   * @var array
   */
  protected $data;

  /**
   * Constructs an Response object.
   *
   * @param array $response
   *
   */
  public function __construct($response) {
    $this->code = $response->code;
    $this->request = $response->request;
    $this->headers = $response->headers;
    $this->protocol = $response->protocol;

    // Do not throws an exception for 503 Service Unavailable.
    $this->data = @drupal_json_decode($response->data);
  }

  /**
   * @return int
   *   An integer containing the response status code, or the error code if an
   *   error occurred.
   */
  public function getCode() {
    return $this->code;
  }

  /**
   * @return string
   *   The response protocol (e.g. HTTP/1.1 or HTTP/1.0).
   */
  public function getProtocol() {
    return $this->protocol;
  }

  /**
    * @return string
    *   A string containing the request body that was sent.
    */
   public function getRequest() {
     return $this->request;
   }

  /**
   * Gets a response header.
   *
   * @param string $key
   *   The header to return.
   *
   * @return string
   *   The header value.
   */
  public function getHeader($key) {
    return $this->headers[$key];
  }

  /**
   * @return array
   *   An array containing the response headers as name/value pairs. HTTP header
   *   names are case-insensitive (RFC 2616, section 4.2), so for easy access
   *   the array keys are returned in lower case.
   */
  public function getHeaders() {
    return $this->headers;
  }

  /**
   * Gets response data.
   *
   * @param string $key
   *   (Optional) A key to return.
   *
   * @return array|mixed
   *   The data returned by the service as an array or a value if a key was given.
   */
  public function getData($key = NULL) {
    return isset($key) && isset($this->data[$key]) ? $this->data[$key] : $this->data;
  }

  /**
   * @return \DateTime
   *   The date with which the request was made.
   */
  public function getDate() {
    // 'Date' header is actually 'date' header (lower case) in Drupal 7.
    return \DateTime::createFromFormat("D, d M Y H:i:s T", $this->headers['date']);
  }

}
