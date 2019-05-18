<?php

namespace Drupal\helper\Response;

use GuzzleHttp\Psr7\Response;

/**
 * A JSON PSR-7 response implementation.
 *
 * Allows creating a response by passing data to the constructor; by default,
 * serializes the data to JSON, sets a status code of 200 and sets the
 * Content-Type header to application/json.
 */
class JsonEncodeResponse extends Response {

  /**
   * {@inheritdoc}
   *
   * @param mixed $data
   *   The data to be JSON encoded in the response.
   * @param int $status
   *   Status code
   * @param array $headers
   *   Response headers
   * @param string $version
   *   Protocol version
   * @param string|null $reason
   *   Reason phrase (when empty a default will be used based on the status
   *   code).
   */
  public function __construct($data, $status = 200, array $headers = [], $version = '1.1', $reason = NULL) {
    $body =  \GuzzleHttp\json_encode($data);
    $headers += [
      'Content-Type' => 'application/json',
    ];

    parent::__construct($status, $headers, $body, $version, $reason);
  }

}