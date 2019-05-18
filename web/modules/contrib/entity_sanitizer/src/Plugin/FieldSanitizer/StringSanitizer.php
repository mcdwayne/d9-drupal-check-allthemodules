<?php

namespace Drupal\entity_sanitizer\Plugin\FieldSanitizer;

use Drupal\entity_sanitizer\FieldSanitizerBase;

/**
 * Handles sanitizing for the string field types.
 *
 * @package Drupal\entity_sanitizer\Plugin\FieldSanitizer
 *
 * @FieldSanitizer(
 *   id = "string",
 *   label = @Translation("Sanitizer for string type fields")
 * )
 */
class StringSanitizer extends FieldSanitizerBase {

  /**
   * {@inheritdoc}
   */
  public function getFieldValues($table_name, $field_name, $columns) {
    $fields = [
      $field_name . '_value' => "CONCAT_WS(' ', 'Sanitized ', {$table_name}.bundle, '{$field_name} field of type {$this->getPluginId()}', {$table_name}.entity_id, {$table_name}.revision_id, {$table_name}.langcode, {$table_name}.delta)",
    ];

    // Ensure we don't break fields with a max length.
    if (!empty($columns['value']['length']) && is_numeric($columns['value']['length'])) {
      $fields[$field_name . '_value'] = 'SUBSTRING(' . $fields[$field_name . '_value'] . ', 1, ' . $columns['value']['length'] . ')';
    }

    return $fields;
  }
}