<?php

namespace Drupal\authorization_code\Exceptions;

use Drupal\user\UserInterface;

/**
 * Failed to send code exception.
 */
class FailedToSendCodeException extends \Exception {

  /**
   * FailedToSendCodeException constructor.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user.
   * @param \Throwable|null $previous
   *   The previous exception.
   */
  public function __construct(UserInterface $user, \Throwable $previous = NULL) {
    parent::__construct(sprintf('Failed to send code to %s (uid: %d)', $user->getAccountName(), $user->id()), 0, $previous);
  }

}
