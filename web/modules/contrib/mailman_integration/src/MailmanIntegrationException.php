<?php

namespace Drupal\mailman_integration;

/**
 * Mailman Integration module error define.
 */
class MailmanIntegrationException extends \RuntimeException {
  const USER_INPUT = 1;
  const INVALID_URL = 2;
  const HTML_FETCH = 3;
  const HTML_PARSE = 4;
  const INVALID_OPTION = 5;
  const NO_MATCH = 6;

}
