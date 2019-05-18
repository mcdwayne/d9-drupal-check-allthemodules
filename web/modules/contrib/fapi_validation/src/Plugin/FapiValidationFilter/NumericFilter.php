<?php

namespace Drupal\fapi_validation\Plugin\FapiValidationFilter;

use Drupal\fapi_validation\FapiValidationFiltersInterface;

/**
 * Fapi Validation Plugin for remove Numeric filter.
 *
 * @FapiValidationFilter(
 *   id = "numeric"
 * )
 */
class NumericFilter implements FapiValidationFiltersInterface {

  /**
   * {@inheritdoc}
   */
  public function filter($value) {
    return preg_replace('/[^0-9]+/', '', $value);
  }

}
