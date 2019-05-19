<?php

namespace Drupal\simple_integrations\Exception;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Inactive integration exception.
 */
class IntegrationInactiveException extends BadRequestHttpException {

  /**
   * Supply an exception message.
   *
   * @param string $integration_id
   *   An integration ID.
   */
  public function __construct($integration_id) {
    sprintf('The integration %s is not currently active, and cannot be used.', $integration_id);
  }

}
