<?php

namespace Drupal\entity_sanitizer\Plugin\FieldSanitizer;

use Drupal\entity_sanitizer\FieldSanitizerBase;

/**
 * Handles sanitizing for the image field types.
 *
 * We only sanitize image titles and alt tags. The files themselves are
 * handles in the file entity sanitation.
 *
 * @package Drupal\entity_sanitizer\Plugin\FieldSanitizer
 *
 * @FieldSanitizer(
 *   id = "image",
 *   label = @Translation("Sanitizer for image type fields")
 * )
 */
class ImageSanitizer extends FieldSanitizerBase {

  /**
   * {@inheritdoc}
   */
  public function getFieldValues($table_name, $field_name, $columns) {
    $fields = [
      $field_name . '_alt' => "CONCAT_WS(' ', 'Sanitized alt for ', {$table_name}.bundle, '{$field_name} field of type image', {$table_name}.entity_id, {$table_name}.revision_id, {$table_name}.langcode, {$table_name}.delta)",
      $field_name . '_title' => "CONCAT_WS(' ', 'Sanitized title for ', {$table_name}.bundle, '{$field_name} field of type image', {$table_name}.entity_id, {$table_name}.revision_id, {$table_name}.langcode, {$table_name}.delta)",
    ];

    return $fields;
  }
}