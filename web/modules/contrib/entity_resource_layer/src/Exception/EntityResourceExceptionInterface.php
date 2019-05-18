<?php

namespace Drupal\entity_resource_layer\Exception;

/**
 * Interface for entity resource exceptions.
 *
 * @package Drupal\entity_resource_layer
 */
interface EntityResourceExceptionInterface {

  /**
   * Gets resource data of the exception.
   *
   * @param bool $includeCode
   *   Whether to include the exception code in the data.
   *
   * @return array
   *   Serializable data array.
   */
  public function getResourceData($includeCode = FALSE);

}
