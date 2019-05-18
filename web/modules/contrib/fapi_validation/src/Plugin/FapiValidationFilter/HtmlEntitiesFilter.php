<?php

namespace Drupal\fapi_validation\Plugin\FapiValidationFilter;

use Drupal\fapi_validation\FapiValidationFiltersInterface;

/**
 * Fapi Validation Plugin for HTML Entities filter.
 *
 * @FapiValidationFilter(
 *   id = "html_entitites"
 * )
 */
class HtmlEntitiesFilter implements FapiValidationFiltersInterface {

  /**
   * {@inheritdoc}
   */
  public function filter($value) {
    return htmlentities(html_entity_decode($value));
  }

}
