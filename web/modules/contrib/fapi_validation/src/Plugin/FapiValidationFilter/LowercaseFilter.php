<?php

namespace Drupal\fapi_validation\Plugin\FapiValidationFilter;

use Drupal\fapi_validation\FapiValidationFiltersInterface;

/**
 * Fapi Validation Plugin for Lowercase filter.
 *
 * @FapiValidationFilter(
 *   id = "lowercase"
 * )
 */
class LowercaseFilter implements FapiValidationFiltersInterface {

  /**
   * {@inheritdoc}
   */
  public function filter($value) {
    return function_exists('mb_strtolower') ? mb_strtolower($value) : strtolower($value);
  }

}
