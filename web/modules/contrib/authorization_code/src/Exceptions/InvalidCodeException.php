<?php

namespace Drupal\authorization_code\Exceptions;

/**
 * Invalid code exception.
 */
class InvalidCodeException extends \Exception {

  /**
   * InvalidCodeException constructor.
   *
   * @param \Throwable|null $previous
   *   The previous exception.
   */
  public function __construct(\Throwable $previous = NULL) {
    parent::__construct('Received authentication code is invalid', $previous);
  }

}
