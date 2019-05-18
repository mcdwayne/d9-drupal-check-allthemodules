<?php

namespace CleverReach\Infrastructure\Utility;

/**
 *
 */
class HttpResponse {
  const CLASS_NAME = __CLASS__;

  /**
   * @var int
   */
  private $status;

  /**
   * @var string
   */
  private $body;

  /**
   * @var array
   */
  private $headers;

  /**
   *
   */
  public function __construct($status, $headers, $body) {
    $this->status = $status;
    $this->headers = $headers;
    $this->body = $body;
  }

  /**
   * Return response status.
   *
   * @return int
   */
  public function getStatus() {
    return $this->status;
  }

  /**
   * Return response body.
   *
   * @return string
   */
  public function getBody() {
    return $this->body;
  }

  /**
   * Return response headers.
   *
   * @return array
   */
  public function getHeaders() {
    return $this->headers;
  }

  /**
   *
   */
  public function isSuccessful() {
    return $this->getStatus() >= 200 && $this->getStatus() < 300;
  }

}
