<?php

namespace Drupal\entity_resource_layer\Exception;

use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * Resource validation specific exception.
 *
 * @package Drupal\entity_resource_layer\Exception
 */
class EntityResourceException extends UnprocessableEntityHttpException implements EntityResourceExceptionInterface {

  /**
   * {@inheritdoc}
   */
  public function getResourceData($includeCode = FALSE) {
    return [
      'message' => $this->getMessage(),
    ];
  }

}
