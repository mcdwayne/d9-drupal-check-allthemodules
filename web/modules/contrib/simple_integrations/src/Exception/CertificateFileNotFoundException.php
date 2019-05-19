<?php

namespace Drupal\simple_integrations\Exception;

/**
 * Certificate file not found exception.
 */
class CertificateFileNotFoundException extends InvalidArgumentException {

  /**
   * Supply an exception message.
   */
  public function __construct() {
    sprintf('No file could be found in the location specified.');
  }

}
