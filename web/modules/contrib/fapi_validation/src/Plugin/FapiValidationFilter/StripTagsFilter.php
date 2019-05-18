<?php

namespace Drupal\fapi_validation\Plugin\FapiValidationFilter;

use Drupal\fapi_validation\FapiValidationFiltersInterface;

/**
 * Fapi Validation Plugin for Strip HTML tags filter.
 *
 * @FapiValidationFilter(
 *   id = "strip_tags"
 * )
 */
class StripTagsFilter implements FapiValidationFiltersInterface {

  /**
   * {@inheritdoc}
   */
  public function filter($value) {
    return strip_tags($value);
  }

}
