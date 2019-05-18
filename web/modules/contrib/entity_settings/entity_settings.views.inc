<?php

/**
 * @file
 * Provide Views data for Entity Settings module.
 *
 * @ingroup views_module_handlers
 */

use Drupal\field\FieldStorageConfigInterface;

/**
 * Implements hook_field_views_data().
 *
 * Views integration for setting fields.
 */
function entity_settings_field_views_data(FieldStorageConfigInterface $field) {
  $data = views_field_default_views_data($field);

  foreach ($data as $table_name => $table_data) {
    foreach ($table_data as $field_name => $field_data) {
      if (isset($field_data['filter']) && $field_name != 'delta') {
        $data[$table_name][$field_name]['filter']['id'] = 'list_field';
      }
      if (isset($field_data['argument']) && $field_name != 'delta') {
        switch ($field->getType()) {
          case 'setting_list_integer':
            $data[$table_name][$field_name]['argument']['id'] = 'string_list_field';
            break;

          case 'setting_list_string':
            $data[$table_name][$field_name]['argument']['id'] = 'number_list_field';
            break;
        }
      }
    }
  }

  return $data;
}
