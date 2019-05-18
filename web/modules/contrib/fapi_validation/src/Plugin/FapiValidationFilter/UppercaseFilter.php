<?php

namespace Drupal\fapi_validation\Plugin\FapiValidationFilter;

use Drupal\fapi_validation\FapiValidationFiltersInterface;

/**
 * Fapi Validation Plugin for Uppercase filter.
 *
 * @FapiValidationFilter(
 *   id = "uppercase"
 * )
 */
class UppercaseFilter implements FapiValidationFiltersInterface {

  /**
   * {@inheritdoc}
   */
  public function filter($value) {
    return function_exists('mb_strtoupper') ? mb_strtoupper($value) : strtoupper($value);
  }

}
