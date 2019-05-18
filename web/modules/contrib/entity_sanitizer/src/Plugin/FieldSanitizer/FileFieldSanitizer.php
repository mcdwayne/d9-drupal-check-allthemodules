<?php

namespace Drupal\entity_sanitizer\Plugin\FieldSanitizer;

use Drupal\entity_sanitizer\FieldSanitizerBase;

/**
 * Handles sanitizing for the file field types.
 *
 * For file fields we only change the description. File names and contents
 * are handled in the file entity.
 *
 * @package Drupal\entity_sanitizer\Plugin\FieldSanitizer
 *
 * @FieldSanitizer(
 *   id = "file",
 *   label = @Translation("Sanitizer for file type fields")
 * )
 */
class FileFieldSanitizer extends FieldSanitizerBase {

  /**
   * {@inheritdoc}
   */
  public function getFieldValues($table_name, $field_name, $columns) {
    $fields = [
      $field_name . '_description' => "CONCAT_WS(' ', 'Sanitized ', {$table_name}.bundle, '{$field_name} field of type {$field_type}', {$table_name}.entity_id, {$table_name}.revision_id, {$table_name}.langcode, {$table_name}.delta)",
    ];

    return $fields;
  }
}