<?php

namespace Drupal\authorization_code\Exceptions;

/**
 * Invalid login process configuration.
 */
class InvalidLoginProcessConfiguration extends \Exception {

  /**
   * InvalidLoginProcessConfiguration constructor.
   *
   * @param string $message
   *   The exception message.
   * @param \Throwable|null $previous
   *   The previous exception.
   */
  public function __construct(string $message, \Throwable $previous = NULL) {
    parent::__construct($message, 0, $previous);
  }

}
