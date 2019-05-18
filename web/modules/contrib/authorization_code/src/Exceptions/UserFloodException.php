<?php

namespace Drupal\authorization_code\Exceptions;

use Drupal\authorization_code\Entity\LoginProcess;
use Throwable;

/**
 * Flood exception.
 */
class UserFloodException extends \Exception {

  /**
   * UserFloodException constructor.
   *
   * @param \Drupal\authorization_code\Entity\LoginProcess $login_process
   *   The login process.
   * @param mixed $identifier
   *   The identifier.
   * @param \Throwable|null $previous
   *   The previous exception (or null).
   */
  public function __construct(LoginProcess $login_process, $identifier, Throwable $previous = NULL) {
    parent::__construct(sprintf('User flood exception for: %s:%s.', $login_process->id(), $identifier), 0, $previous);
  }

}
