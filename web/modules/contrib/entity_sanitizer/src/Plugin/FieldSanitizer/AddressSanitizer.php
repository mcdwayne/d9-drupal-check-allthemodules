<?php

namespace Drupal\entity_sanitizer\Plugin\FieldSanitizer;

use Drupal\entity_sanitizer\FieldSanitizerBase;

/**
 * Handles sanitizing for the address field types.
 *
 * For addresses we change only person specific details. We can leave
 * country and city as they're broad enough. Other values we set to NULL
 * to be sure there's no data remaining.
 *
 * @package Drupal\entity_sanitizer\Plugin\FieldSanitizer
 *
 * @FieldSanitizer(
 *   id = "address",
 *   label = @Translation("Sanitizer for address type fields")
 * )
 */
class AddressSanitizer extends FieldSanitizerBase {

  /**
   * {@inheritdoc}
   */
  public function getFieldValues($table_name, $field_name, $columns) {
    $fields = [
      $field_name . '_postal_code' => "'1234 AB'",
      $field_name . '_administrative_area' => "NULL",
      $field_name . '_locality' => "NULL",
      $field_name . '_dependent_locality' => "NULL",
      $field_name . '_postal_code' => "NULL",
      $field_name . '_sorting_code' => "NULL",
      $field_name . '_address_line1' => "CONCAT_WS(' ', 'Address ', {$table_name}.bundle, {$table_name}.entity_id, {$table_name}.revision_id)",
      $field_name . '_address_line2' => "CONCAT_WS(' ', '{$field_name}', {$table_name}.langcode, {$table_name}.delta)",
      $field_name . '_organization ' => "NULL",
      $field_name . '_given_name' => "NULL",
      $field_name . '_additional_name' => "NULL",
      $field_name . '_family_name' => "NULL",
    ];

    return $fields;
  }
}