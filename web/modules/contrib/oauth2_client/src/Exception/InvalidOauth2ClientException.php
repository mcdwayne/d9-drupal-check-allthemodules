<?php

namespace Drupal\oauth2_client\Exception;

/**
 * Exception thrown when trying to retrieve a non-existent OAuth2 Client.
 */
class InvalidOauth2ClientException extends \Exception {

  /**
   * Constructs an InvalidOauth2ClientException object.
   *
   * @param string $invalidClientId
   *   The passed Oauth2 Client ID that was found to be invalid.
   * @param string $message
   *   The Exception message to throw.
   * @param int $code
   *   The Exception code.
   * @param \Throwable $previous
   *   The previous exception used for the exception chaining.
   */
  public function __construct($invalidClientId, $message = "", $code = 0, \Throwable $previous = NULL) {
    if ($message == "") {
      if (is_scalar($invalidClientId)) {
        $message = "The OAuth2 Client plugin '" . $invalidClientId . "' does not exist";
      }
      else {
        $message = 'An invalid value was passed for the OAuth2 Plugin ID';
      }
    }

    parent::__construct($message, $code, $previous);
  }

}
