<?php

namespace Drupal\oauth2_client\Exception;

/**
 * Exception thrown when the Oauth2 Client plugin is missing a required key.
 */
class Oauth2ClientPluginMissingKeyException extends \Exception {

  /**
   * Constructs an Oauth2ClientPluginMissingKeyException object.
   *
   * @param string $key
   *   The OAuth2 Client plugin key that is missing.
   * @param string $message
   *   The Exception message to throw.
   * @param int $code
   *   The Exception code.
   * @param \Throwable $previous
   *   The previous exception used for the exception chaining.
   */
  public function __construct($key, $message = "", $code = 0, \Throwable $previous = NULL) {
    if ($message == '') {
      $message = 'The Oauth2 Client plugin is missing required key: ' . $key;
    }

    parent::__construct($message, $code, $previous);
  }

}
