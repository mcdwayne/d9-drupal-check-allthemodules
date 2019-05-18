<?php

/**
 * @file
 * Provide Views data for flexible_list.module.
 *
 * @ingroup views_module_handlers
 */

use Drupal\field\FieldStorageConfigInterface;

/**
 * Implements hook_field_views_data().
 *
 * Views integration for flexible list fields. Have a different filter handler and
 * argument handlers for flexible list fields. This should allow to select values of
 * the list.
 */
function flexible_list_field_views_data(FieldStorageConfigInterface $field) {
  $data = views_field_default_views_data($field);

  foreach ($data as $table_name => $table_data) {
    foreach ($table_data as $field_name => $field_data) {
      if (isset($field_data['filter']) && $field_name != 'delta') {
        $data[$table_name][$field_name]['filter']['id'] = 'flexible_list_field';
      }
      if (isset($field_data['argument']) && $field_name != 'delta') {
        if ($field->getType() == 'flexible_list_string') {
          $data[$table_name][$field_name]['argument']['id'] = 'flexible_list_string_field';
        }
        else {
          $data[$table_name][$field_name]['argument']['id'] = 'flexible_list_number_field';
        }
      }
    }
  }

  return $data;
}
