<?php

namespace Drupal\simple_integrations\Exception;

/**
 * Incorrect connection credentials.
 */
class IncorrectConnectionCredentialsException extends InvalidArgumentException {

  /**
   * Supply an exception message.
   */
  public function __construct() {
    sprintf('Incorrect connection credentials were supplied, and the connection failed.');
  }

}
