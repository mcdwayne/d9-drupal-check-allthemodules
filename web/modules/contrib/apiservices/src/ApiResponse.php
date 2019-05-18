<?php

/**
 * @file
 * Contains \Drupal\apiservices\ApiResponse.
 */

namespace Drupal\apiservices;

/**
 * An endpoint response messsage.
 *
 * This class allows the serialization and storage of API responses since
 * attempting to serialize a PSR-7 response results in a serialized version of
 * the underlying stream object and not the actual response body.
 */
class ApiResponse implements ApiResponseInterface {

  /**
   * The body of the response.
   *
   * @var string
   */
  protected $body;

  /**
   * The headers sent with the response.
   *
   * @var array
   */
  protected $headers = [];

  /**
   * The HTTP version.
   *
   * @var string
   */
  protected $protocol;

  /**
   * The HTTP reason message.
   *
   * @var string
   */
  protected $reason;

  /**
   * The HTTP status code.
   *
   * @var int
   */
  protected $statusCode;

  /**
   * Constructs an API response object.
   *
   * @param array $headers
   *   A list of headers sent with the response.
   * @param string $body
   *   The response body.
   * @param int $status_code
   *   The HTTP status code.
   * @param string $reason
   *   The HTTP reason message.
   * @param string $protocol
   *   The HTTP version.
   */
  public function __construct(array $headers, $body, $status_code, $reason, $protocol) {
    $this->body = $body;
    $this->headers = $headers;
    $this->protocol = $protocol;
    $this->reason = $reason;
    $this->statusCode = $status_code;
  }

  /**
   * {@inheritdoc}
   */
  public function getBody() {
    return $this->body;
  }

  /**
   * {@inheritdoc}
   */
  public function getHeader($header) {
    $header = strtolower($header);
    if (isset($this->headers[$header])) {
      return $this->headers[$header];
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getHeaders() {
    return $this->headers;
  }

  /**
   * {@inheritdoc}
   */
  public function getProtocol() {
    return $this->protocol;
  }

  /**
   * {@inheritdoc}
   */
  public function getReason() {
    return $this->reason;
  }

  /**
   * {@inheritdoc}
   */
  public function getStatusCode() {
    return $this->statusCode;
  }

  /**
   * Determines if the server handled the request successfully.
   *
   * @param bool $strict
   *   (optional) If TRUE, only responses with a status code of 200 (OK) will
   *   be considered successful, otherwise 302 (Found) and 307 (Temporary
   *   Redirect) codes will be allowed as well. Defaults to TRUE.
   *
   * @return bool
   *   If the status code indicates a successful request, TRUE, otherwise FALSE.
   */
  public function isSuccessful($strict = TRUE) {
    if ($strict) {
      return $this->statusCode == 200;
    }
    return $this->statusCode == 200 || $this->statusCode == 302 || $this->statusCode == 307;
  }

  /**
   * {@inheritdoc}
   */
  public function withHeaders(array $headers) {
    $response = clone $this;
    $response->headers = $headers;
    return $response;
  }

}
