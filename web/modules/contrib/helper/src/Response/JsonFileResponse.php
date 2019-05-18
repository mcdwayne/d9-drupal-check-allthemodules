<?php

namespace Drupal\helper\Response;

use GuzzleHttp\Psr7\Response;

/**
 * A JSON PSR-7 response implementation.
 *
 * Allows creating a response by passing a file URI to the constructor;
 * by default, loads the data from the file as the body (assumes the file
 * already contains serialized JSON), sets a status code of 200 and sets the
 * Content-Type header to application/json.
 */
class JsonFileResponse extends Response {

  /**
   * {@inheritdoc}
   *
   * @param string $uri
   *   The URI containing an encoded JSON string.
   */
  public function __construct($uri, $status = 200, array $headers = [], $version = '1.1', $reason = NULL) {
    if (!is_file($uri)) {
      throw new \InvalidArgumentException("URI {$uri} is not a file.");
    }
    if (!is_readable($uri)) {
      throw new \InvalidArgumentException("URI {$uri} is not readable.");
    }

    $body = fopen($uri, 'r');
    $headers += [
      'Content-Type' => 'application/json',
    ];

    parent::__construct($status, $headers, $body, $version, $reason);
  }

}
