<?php

namespace Drupal\fapi_validation\Plugin\FapiValidationFilter;

use Drupal\fapi_validation\FapiValidationFiltersInterface;

/**
 * Fapi Validation Plugin for Trim filter.
 *
 * @FapiValidationFilter(
 *   id = "trim"
 * )
 */
class TrimFilter implements FapiValidationFiltersInterface {

  /**
   * {@inheritdoc}
   */
  public function filter($value) {
    return trim($value);
  }

}
