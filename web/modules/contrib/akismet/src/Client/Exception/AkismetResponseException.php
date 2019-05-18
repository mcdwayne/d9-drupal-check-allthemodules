<?php

namespace Drupal\akismet\Client\Exception;

/**
 * Akismet server response exception.
 *
 * Thrown when a request to a Akismet server succeeds, but the response does not
 * contain an expected element; e.g., a backend configuration or execution
 * error that possibly exists on one server only.
 *
 * @see Akismet::handleRequest()
 */
class AkismetResponseException extends AkismetException {
  /**
   * Overrides AkismetException::$severity.
   *
   * Might be a client-side error, but more likely a server-side error. The
   * client may be able to recover from this error.
   */
  protected $severity = 'debug';
}
