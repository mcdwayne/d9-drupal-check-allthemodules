<?php

namespace Drupal\matomo_reporting_api\Exception;

/**
 * Exception intended to be thrown when the Matomo server URL is missing.
 */
class MissingMatomoServerUrlException extends \Exception {

  /**
   * Constructs a new MissingMatomoServerUrlException.
   */
  public function __construct($message = "", $code = 0, \Throwable $previous = NULL) {
    $message = $message ?: 'The URL of the Matomo server is not configured';
    parent::__construct($message, $code, $previous);
  }

}
