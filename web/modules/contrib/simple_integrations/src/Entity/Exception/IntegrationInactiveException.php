<?php

namespace Drupal\simple_integrations\Entity\Exception;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * ASSD invalid callback exception.
 */
class IntegrationInactiveException extends BadRequestHttpException {

  /**
   * Supply an exception message.
   *
   * @param string $integration_id
   *   An integration ID.
   */
  public function __construct($integration_id) {
    sprintf('The integration %integration is not currently active, and cannot be used.', $integration_id);
  }

}
