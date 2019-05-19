<?php

namespace Drupal\simple_integrations\Exception;

use Exception;

/**
 * No debug message was provided.
 */
class EmptyDebugMessageException extends Exception {

  /**
   * Supply an exception message.
   */
  public function __construct() {
    sprintf('No debug message was provided, so no debugging could take place.');
  }

}
