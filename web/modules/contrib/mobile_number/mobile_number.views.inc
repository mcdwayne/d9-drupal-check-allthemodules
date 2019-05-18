<?php

/**
 * @file
 * mobile_number.views.inc
 */

use Drupal\field\FieldStorageConfigInterface;

/**
 * Implements hook_field_views_data().
 */
function mobile_number_field_views_data(FieldStorageConfigInterface $field) {
  $data = views_field_default_views_data($field);

  if ($field->getType() == 'mobile_number') {
    foreach ($data as $table_name => $table_data) {
      foreach ($table_data as $field_name => $field_data) {
        if ($field_name == "{$field->getName()}_country") {
          $data[$table_name][$field_name]['filter']['id'] = 'in_operator';
          $data[$table_name][$field_name]['filter']['options callback'] = '\Drupal\mobile_number\Plugin\Field\FieldType\MobileNumberItem::countryOptions';
        }
        if ($field_name == "{$field->getName()}_tfa") {
          $data[$table_name][$field_name]['filter']['id'] = 'in_operator';
          $data[$table_name][$field_name]['filter']['options callback'] = '\Drupal\mobile_number\Plugin\Field\FieldType\MobileNumberItem::booleanOptions';
        }
        if ($field_name == "{$field->getName()}_verified") {
          $data[$table_name][$field_name]['filter']['id'] = 'in_operator';
          $data[$table_name][$field_name]['filter']['options callback'] = '\Drupal\mobile_number\Plugin\Field\FieldType\MobileNumberItem::booleanOptions';
        }
      }
    }
  }

  return $data;
}
