<?php

namespace Drupal\entity_sanitizer\Plugin\FieldSanitizer;

use Drupal\entity_sanitizer\FieldSanitizerBase;

/**
 * Handles sanitizing for the email field types.
 *
 * @package Drupal\entity_sanitizer\Plugin\FieldSanitizer
 *
 * @FieldSanitizer(
 *   id = "email",
 *   label = @Translation("Sanitizer for email type fields")
 * )
 */
class EmailSanitizer extends FieldSanitizerBase {

  /**
   * {@inheritdoc}
   */
  public function getFieldValues($table_name, $field_name, $columns) {
    $fields = [
      $field_name . '_value' => "CONCAT({$table_name}.bundle, '+', {$table_name}.entity_id, '-', {$table_name}.revision_id, {$table_name}.langcode, {$table_name}.delta, '@{$field_name}.com')",
    ];

    return $fields;
  }
}