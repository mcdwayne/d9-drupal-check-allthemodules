<?php

/**
 * @file
 * Contains Views hooks.
 */

/**
 * Implements hook_field_views_data_alter().
 */
function views_sort_null_field_field_views_data_alter(array &$data, \Drupal\field\FieldStorageConfigInterface $field_storage) {
  $entity_type_id = $field_storage->getTargetEntityTypeId();
  $entity_type = \Drupal::entityTypeManager()->getDefinition($entity_type_id);
  $field_name = $field_storage->getName();
  list($label) = views_entity_field_label($entity_type_id, $field_name);
  $args = array(
    '@label' => $label,
    '@name' => $field_name,
  );
  $table_mapping = \Drupal::entityTypeManager()->getStorage($entity_type_id)->getTableMapping();
  $field_columns = $field_storage->getColumns();
  $table_alias = $entity_type_id . '__' . $field_name;

  foreach ($field_columns as $column => $attributes) {
    // Skip columns that can't be NULL: our filter is pointless for these.
    if (!empty($attributes['not null'])) {
      continue;
    }

    $args['@column'] = $column;
    $column_real_name = $table_mapping->getFieldColumnName($field_storage, $column);

    if (count($field_columns) == 1 || $column == 'value') {
      $title = t('@label (@name) null sort', $args);
      $title_short = t("@label null sort", $args);
    }
    else {
      $title = t('@label (@name:@column) null sort', $args);
      $title_short = t('@label:@column null sort', $args);
    }
    $data[$table_alias][$column_real_name . '_null_sort'] = array(
      'title' => $title,
      'title short' => $title_short,
      'group' => $entity_type->getLabel(),
      'help' => t('Sort entities with no value (NULL) last or first.'),
      'sort' => array(
        'field' => $column_real_name,
        'id' => 'null_sort',
      ),
    );
  }
}
