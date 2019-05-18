<?php

/**
 * @file
 * Contains \Drupal\apiservices\GuzzleResponse.
 */

namespace Drupal\apiservices;

use Drupal\apiservices\Exception\EndpointException;
use GuzzleHttp\Psr7\Response;

/**
 * An endpoint response created from a Guzzle HTTP client.
 */
class GuzzleResponse extends ApiResponse {

  /**
   * Indicates whether this response was loaded from a cache.
   *
   * @var bool
   */
  protected $modified = TRUE;

  /**
   * A decompressed (and if possible, JSON-decoded) version of the response body.
   *
   * @var mixed
   */
  protected $processedBody = NULL;

  /**
   * Constructs a GuzzleResponse object.
   *
   * @param \GuzzleHttp\Psr7\Response $response
   *   The response returned by a Guzzle client.
   */
  public function __construct(Response $response) {
    parent::__construct([], '', $response->getStatusCode(), $response->getReasonPhrase(), $response->getProtocolVersion());
    $this->importBody($response);
    $this->importHeaders($response);
  }

  /**
   * Removes unnecessary properties prior to serialization.
   *
   * @return array
   *   An array of properties that should be serialized.
   */
  public function __sleep() {
    $vars = get_object_vars($this);
    unset($vars['modified'], $vars['processedBody']);
    return array_keys($vars);
  }

  /**
   * Reinitializes properties after being unserialized.
   */
  public function __wakeup() {
    // If the response was serialized, then it was probably cached, which means
    // that existing data is not being changed.
    $this->modified = FALSE;
  }

  /**
   * Processes the body of a Guzzle response.
   *
   * If the body is JSON-encoded, it will be decoded automatically.
   *
   * @param \GuzzleHttp\Psr7\Response $response
   *   The HTTP response.
   */
  protected function importBody(Response $response) {
    $this->body = (string) $response->getBody();
  }

  /**
   * Processes the headers of a Guzzle response.
   *
   * @param \GuzzleHttp\Psr7\Response $response
   *   The HTTP response.
   */
  protected function importHeaders(Response $response) {
    // Convert headers from an array format to a string.
    foreach ($response->getHeaders() as $name => $value) {
      $name = strtolower($name);
      $this->headers[$name] = implode(', ', $value);
    }
  }

  /**
   * Gets the response body.
   *
   * If the content was compressed, it will be decompressed when possible.
   *
   * @return mixed
   *   A string containing the response body, or an array if the content was
   *   JSON-encoded.
   */
  public function getBody() {
    if ($this->processedBody === NULL) {
      $this->processedBody = $this->body;

      // Content should not be decoded twice if the 'decode_content' request
      // option was set because Guzzle automatically removes this header as well.
      $encoding = $this->getHeader('Content-Encoding');
      if (extension_loaded('zlib') && $encoding == 'gzip') {
        // Suppress 'data error' warnings.
        $this->processedBody = @gzinflate(substr($this->processedBody, 10, -8));
        if ($this->processedBody === FALSE) {
          throw new EndpointException('Unable to decode compressed response');
        }
      }

      // Some poorly implemented APIs respond with JSON content, but include an
      // incorrect header like 'text/html'. Modules that interact with those
      // servers will likely still have to do their own decoding.
      $type = $this->getHeader('Content-Type');
      if (strpos($type, 'application/json') !== FALSE) {
        $data = json_decode($this->processedBody, TRUE);
        if ($data === NULL) {
          throw new EndpointException('Unable to decode JSON response');
        }
        $this->processedBody = $data;
      }
    }

    return $this->processedBody;
  }

  /**
   * Gets the unprocessed response body.
   *
   * @return string
   *   A string containing the response body as returned by the HTTP client.
   *
   * @see GuzzleResponse::getBody()
   */
  public function getBodyRaw() {
    return $this->body;
  }

  /**
   * Determines if this request was retrieved from a cache.
   *
   * @return bool
   *   If the request was retrieved from a cache, FALSE, otherwise TRUE.
   */
  public function isModified() {
    return $this->modified;
  }

}
