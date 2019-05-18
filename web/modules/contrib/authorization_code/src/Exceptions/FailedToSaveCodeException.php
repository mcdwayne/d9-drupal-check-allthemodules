<?php

namespace Drupal\authorization_code\Exceptions;

/**
 * Failed to save code exception.
 */
class FailedToSaveCodeException extends \Exception {

  /**
   * FailedToSaveCodeException constructor.
   *
   * @param \Throwable|null $previous
   *   The previous exception.
   */
  public function __construct(\Throwable $previous = NULL) {
    parent::__construct('Failed to save authorization code in code repository.', 0, $previous);
  }

}
