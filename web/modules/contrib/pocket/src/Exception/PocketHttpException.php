<?php

namespace Drupal\pocket\Exception;

use GuzzleHttp\Exception\BadResponseException;

class PocketHttpException extends \Exception {

  protected $original;

  protected $response;

  public function __construct(BadResponseException $exception) {
    $response = $exception->getResponse();
    $header = $response ? $response->getHeader('X-Error') : [];
    parent::__construct($header[0] ?? $exception->getMessage());
    $this->original = $exception;
    $this->response = $response;
  }

  public static function create(BadResponseException $exception) {
    switch ($exception->getCode()) {
      case 403:
        return new AccessDeniedException($exception);
      case 401:
        return new UnauthorizedException($exception);
    }

    return new static($exception);
  }

  public function getHeader(string $name) {
    return $this->response->getHeader($name);
  }

}
