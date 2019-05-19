<?php

/**
 * @file
 * Provide views handler.
 */

use Drupal\field\FieldStorageConfigInterface;

/**
 * Implements hook_field_views_data().
 *
 */
function rut_field_field_views_data(FieldStorageConfigInterface $field_storage) {
  $data = views_field_default_views_data($field_storage);
  $field_name = $field_storage->getName();
  foreach ($data as $table_name => $table_data) {
    if (isset($data[$table_name][$field_name])) {
      $data[$table_name][$field_name]['filter'] = [
        'field' => $field_name,
        'table' => $table_name,
        'id' => 'rut',
        'additional fields' => $data[$table_name][$field_name]['field']['additional fields'],
        'field_name' => $field_name,
        'entity_type' => $data[$table_name][$field_name]['field']['entity_type'],
      ];
    }
  }

  return $data;
}
