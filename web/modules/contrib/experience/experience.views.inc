<?php

/**
 * @file
 * Provides views data for the experience module.
 */

use Drupal\field\FieldStorageConfigInterface;

/**
 * Implements hook_field_views_data().
 */
function experience_field_views_data(FieldStorageConfigInterface $field_storage) {
  $data = views_field_default_views_data($field_storage);
  foreach ($data as $table_name => $table_data) {
    // Set the 'experience' filter type.
    $data[$table_name][$field_storage->getName() . '_value']['filter']['id'] = 'experience';

    // Set the 'experience' argument type.
    $data[$table_name][$field_storage->getName() . '_value']['argument']['id'] = 'experience';
  }

  return $data;
}
