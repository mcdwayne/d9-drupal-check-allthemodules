<?php

namespace Drupal\entity_pilot\Exception;

/**
 * Defines an exception class for transport errors.
 */
class TransportException extends \Exception {

  const VERIFICATION_FAILED = 1;
  const AUTHENTICATION_FAILED = 2;
  const UNKNOWN_EXCEPTION = 3;
  const QUOTA_EXCEEDED = 4;

}
