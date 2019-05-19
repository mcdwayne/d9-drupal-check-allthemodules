<?php

/**
 * @file
 * Contains \Drupal\themekey\Plugin\Property\SystemQueryParam.
 */

namespace Drupal\themekey\Plugin\Property;

use Drupal\themekey\PropertyBase;

/**
 * Provides a 'query param' property.
 *
 * @Property(
 *   id = "system:query_param",
 *   name = @Translation("System: Query Parameter"),
 *   description = @Translation("Every single query parameter other than 'q' and its value, if present. Note that values are url decoded. Example: '?q=node&foo=bar&dummy&filter=tid%3A27' will cause three entries 'foo=bar', 'dummy' and 'filter=tid:27'. For 'q', see property drupal:get_q."),
 *   page_cache_compatible = TRUE,
 * )
 */
class SystemQueryParam extends PropertyBase {

  /**
   * @return array
   *   array of system:query_param values
   */
  public function getValues() {
    // TODO use safe values from RouteMatch
    $filtered_params = array();
    $query_params = $_GET;
    // unset($query_params['q']);
    foreach ($query_params as $key => $value) {
      $filtered_params[] = $key . (!empty($value) ? '=' . $value : '');
      $filtered_params[$key] = !empty($value) ? $value : '';
    }
    return $filtered_params;
  }
}
