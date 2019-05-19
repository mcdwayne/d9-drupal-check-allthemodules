<?php

namespace Drupal\simple_integrations\Exception;

use Exception;

/**
 * Debug mode disabled.
 */
class DebugModeDisabledException extends Exception {

  /**
   * Supply an exception message.
   *
   * @param string $integration_id
   *   An integration ID.
   */
  public function __construct($integration_id) {
    sprintf('Debug mode for the integration %s is not currently active, and cannot be used.', $integration_id);
  }

}
