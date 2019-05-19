<?php

use Drupal\field\FieldStorageConfigInterface;

/**
 * Implements hook_field_views_data_alter().
 */
function views_negate_field_views_data_alter(&$data, FieldStorageConfigInterface $field_storage) {
  if ($field_storage->getType() == 'list_string') {
    $entity_type_id = $field_storage->getTargetEntityTypeId();
    $field_name = $field_storage->getName();
    $table_mapping = \Drupal::entityManager()->getStorage($entity_type_id)->getTableMapping();
    $table_name = $table_mapping->getDedicatedDataTableName($field_storage);
    $field_col = $field_name . '_value';
    $data[$table_name][$field_col]['argument']['id'] = 'StringNegate';
  }
}
