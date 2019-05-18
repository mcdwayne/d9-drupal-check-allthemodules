<?php

namespace Drupal\entity_sanitizer\Plugin\FieldSanitizer;

use Drupal\entity_sanitizer\FieldSanitizerBase;

/**
 * Handles sanitizing for the text_with_summary field types.
 *
 * @package Drupal\entity_sanitizer\Plugin\FieldSanitizer
 *
 * @FieldSanitizer(
 *   id = "text_with_summary",
 *   label = @Translation("Sanitizer for text_with_summary type fields")
 * )
 */
class TextWithSummarySanitizer extends FieldSanitizerBase {

  /**
   * {@inheritdoc}
   */
  public function getFieldValues($table_name, $field_name, $columns) {
    $fields = [
      $field_name . '_value' => "CONCAT_WS(' ', 'Sanitized value for bundle', {$table_name}.bundle, '{$field_name} field of type text_with_Summary', {$table_name}.entity_id, {$table_name}.revision_id, {$table_name}.langcode, {$table_name}.delta)",
      $field_name . '_summary' => "CONCAT_WS(' ', 'Sanitized summary for bundle', {$table_name}.bundle, '{$field_name} field of type text_with_summary', {$table_name}.entity_id, {$table_name}.revision_id, {$table_name}.langcode, {$table_name}.delta)",
    ];

    return $fields;
  }
}