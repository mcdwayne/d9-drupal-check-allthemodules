<?php

namespace Drupal\authorization_code\Exceptions;

/**
 * User not found exception.
 */
class UserNotFoundException extends \Exception {

  /**
   * UserNotFoundException constructor.
   *
   * @param mixed $identifier
   *   The user identifier.
   * @param \Throwable|null $previous
   *   The previous exception.
   */
  public function __construct($identifier, \Throwable $previous = NULL) {
    parent::__construct(sprintf('No user was found with "%s" as the identifier', $identifier), 0, $previous);
  }

}
