<?php

namespace Drupal\entity_sanitizer\Plugin\FieldSanitizer;

use Drupal\entity_sanitizer\FieldSanitizerBase;

/**
 * Handles sanitizing for the link field types.
 *
 * For links we treat both title and destination as sensitive.
 *
 * @package Drupal\entity_sanitizer\Plugin\FieldSanitizer
 *
 * @FieldSanitizer(
 *   id = "link",
 *   label = @Translation("Sanitizer for link type fields")
 * )
 */
class LinkSanitizer extends FieldSanitizerBase {

  /**
   * {@inheritdoc}
   */
  public function getFieldValues($table_name, $field_name, $columns) {
    $fields = [
      $field_name . '_uri' => "'//www.getopensocial.com'",
      $field_name . '_title' => "CONCAT_WS(' ', 'Sanitized title for bundle ', {$table_name}.bundle, '{$field_name} field of type link', {$table_name}.entity_id, {$table_name}.revision_id, {$table_name}.langcode, {$table_name}.delta)",
    ];

    return $fields;
  }
}