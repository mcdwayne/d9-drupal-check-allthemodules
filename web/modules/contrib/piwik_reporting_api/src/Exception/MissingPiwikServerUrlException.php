<?php

namespace Drupal\piwik_reporting_api\Exception;

/**
 * Exception intended to be thrown when the Piwik server URL is missing.
 */
class MissingPiwikServerUrlException extends \Exception {

  /**
   * Constructs a new MissingPiwikServerUrlException.
   */
  public function __construct($message = "", $code = 0, \Throwable $previous = NULL) {
    $message = $message ?: 'The URL of the Piwik server is not configured';
    parent::__construct($message, $code, $previous);
  }

}
