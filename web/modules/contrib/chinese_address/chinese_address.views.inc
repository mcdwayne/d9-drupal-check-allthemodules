<?php

/**
 * @file
 * Provides views data for taxonomy.module.
 */

use Drupal\field\FieldStorageConfigInterface;

/**
 * Implements hook_field_views_data_alter().
 *
 * Views integration for entity reference fields which reference taxonomy terms.
 * Adds a term relationship to the default field data.
 *
 * @see views_field_default_views_data()
 */
function chinese_address_field_views_data_alter(array &$data, FieldStorageConfigInterface $field_storage) 
{
    if ($field_storage->getType() == 'chinese_address_field_type') {
        foreach ($data as $table_name => $table_data) {
            foreach ($table_data as $field_name => $field_data) {
                if (isset($field_data['filter']) &&  (strpos($field_name, "province") !== false)) {
                    $data[$table_name][$field_name]['filter']['id'] = 'chinese_address';
                    $data[$table_name][$field_name]['argument']['id'] = 'chinese_address';
                }
            }
        }
    }
}
