<?php

namespace Drupal\entity_sanitizer\Plugin\FieldSanitizer;

use Drupal\entity_sanitizer\FieldSanitizerBase;

/**
 * Handles sanitizing for the address field types.
 *
 * Change the geolocation field to a random location around the world.
 *
 * @package Drupal\entity_sanitizer\Plugin\FieldSanitizer
 *
 * @FieldSanitizer(
 *   id = "geolocation",
 *   label = @Translation("Sanitizer for geolocation type fields")
 * )
 */
class GeolocationSanitizer extends FieldSanitizerBase {

  /**
   * {@inheritdoc}
   */
  public function getFieldValues($table_name, $field_name, $columns) {
    $fields = [
      $field_name . '_lat' => "RAND() * 180 - 90",
      $field_name . '_lng' => "RAND() * 360 - 180",
      $field_name . '_lat_sin' => "SIN(RADIANS({$field_name}_lat))",
      $field_name . '_lat_cos' => "COS(RADIANS({$field_name}_lat))",
      $field_name . '_lng_rad' => "RADIANS({$field_name}_lng)",
      $field_name . '_data' => "'N;'",
    ];

    return $fields;
  }
}
