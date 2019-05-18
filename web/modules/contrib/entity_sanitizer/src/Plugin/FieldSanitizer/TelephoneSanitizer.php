<?php

namespace Drupal\entity_sanitizer\Plugin\FieldSanitizer;

use Drupal\entity_sanitizer\FieldSanitizerBase;

/**
 * Handles sanitizing for the telephone field types.
 *
 * For telephone numbers we just use a default placeholder.
 *
 * @package Drupal\entity_sanitizer\Plugin\FieldSanitizer
 *
 * @FieldSanitizer(
 *   id = "telephone",
 *   label = @Translation("Sanitizer for telephone type fields")
 * )
 */
class TelephoneSanitizer extends FieldSanitizerBase {

  /**
   * {@inheritdoc}
   */
  public function getFieldValues($table_name, $field_name, $columns) {
    $fields = [
      $field_name . '_value' => "'555 1234567'",
    ];

    return $fields;
  }
}