<?php

namespace Drupal\akismet\Client\Exception;

/**
 * Akismet network error exception.
 *
 * Thrown in case a HTTP request results in code <= 0, denoting a low-level
 * communication error.
 */
class AkismetNetworkException extends AkismetException {
  /**
   * Overrides AkismetException::$severity.
   *
   * The client may be able to recover from this error, so use a warning level.
   */
  protected $severity = 'warning';
}
