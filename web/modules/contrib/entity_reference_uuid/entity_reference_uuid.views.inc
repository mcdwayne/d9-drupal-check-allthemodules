<?php

/**
 * @file
 * Provide views data for entity_reference_uuid
 */

use Drupal\field\FieldStorageConfigInterface;

/**
 * Implements hook_field_views_data().
 *
 * @see core_field_views_data()
 *
 * @param \Drupal\field\FieldStorageConfigInterface $field_storage
 *
 * @return array
 */
function entity_reference_uuid_field_views_data(FieldStorageConfigInterface $field_storage) {
  $data = [];

  // The code below only deals with the Entity reference UUID field type.
  if ($field_storage->getType() != 'entity_reference_uuid') {
    return $data;
  }
  return entity_reference_uuid_field_views_data_helper($field_storage);
}

/**
 * Helper function for entity_reference_uuid_field_views_data()
 *
 * This is a separate function so that other field types extending
 * entity_reference_uuid can call the same code.
 *
 * @param \Drupal\field\FieldStorageConfigInterface $field_storage
 *   A field storage entity.
 *
 * @return array
 */
function entity_reference_uuid_field_views_data_helper(FieldStorageConfigInterface $field_storage) {
  $data = views_field_default_views_data($field_storage);

  $entity_manager = \Drupal::entityTypeManager();
  // The ID of the entity type the field is attached to.
  $entity_type_id = $field_storage->getTargetEntityTypeId();
  /** @var \Drupal\Core\Entity\Sql\DefaultTableMapping $table_mapping */
  $table_mapping = $entity_manager->getStorage($entity_type_id)->getTableMapping();
  // Add a relationship to the target entity type.
  $target_entity_type_id = $field_storage->getSetting('target_type');
  $target_entity_type = $entity_manager->getDefinition($target_entity_type_id);
  $entity_type = $entity_manager->getDefinition($entity_type_id);
  // Handle the case of an entity type without a UUID.
  if (!$target_entity_type || !$target_entity_type->hasKey('uuid')) {
    return $data;
  }
  // The line below is technically wrong since the UUID is not copied to the
  // data table, but we use hook_views_data_alter() to make it appear to
  // be present.
  $target_base_table = $target_entity_type->getDataTable() ?: $target_entity_type->getBaseTable();
  $field_name = $field_storage->getName();

  foreach ($data as $table_name => $table_data) {
    // Provide a relationship for the entity type with the entity reference
    // field.
    $args = [
      '@label' => $target_entity_type->getLabel(),
      '@field_name' => $field_name,
    ];
    $data[$table_name][$field_name]['relationship'] = [
      'title' => t('@label referenced from @field_name', $args),
      'label' => t('@field_name: @label', $args),
      'group' => $entity_type->getLabel(),
      'help' => t('Appears in: @bundles.', ['@bundles' => implode(', ', $field_storage->getBundles())]),
      // @see \Drupal\views\Plugin\views\relationship\Standard
      'id' => 'entity_standard_uuid',
      'base' => $target_base_table,
      'entity base table' => $target_entity_type->getBaseTable(),
      'entity uuid field' => $target_entity_type->getKey('uuid'),
      'entity type' => $target_entity_type_id,
      'base field' => $target_entity_type->getKey('id'),
      'relationship field' => $field_name . '_target_uuid',
    ];

    // Provide a reverse relationship for the entity type that is referenced by
    // the field.
    $args['@entity'] = $entity_type->getLabel();
    $args['@label'] = $target_entity_type->getLowercaseLabel();
    $pseudo_field_name = 'reverse__' . $entity_type_id . '__' . $field_name;
    $data[$target_base_table][$pseudo_field_name]['relationship'] = [
      'title' => t('@entity using @field_name', $args),
      'label' => t('@field_name', ['@field_name' => $field_name]),
      'group' => $target_entity_type->getLabel(),
      'help' => t('Relate each @entity with a @field_name set to the @label.', $args),
      'id' => 'entity_reverse_uuid',
      'base' => $entity_type->getDataTable() ?: $entity_type->getBaseTable(),
      'entity base table' => $entity_type->getBaseTable(),
      'entity_type' => $entity_type_id,
      'base field' => $entity_type->getKey('id'),
      'target base' => $target_base_table,
      'target entity base table' => $target_entity_type->getBaseTable(),
      'target entity uuid field' => $target_entity_type->getKey('uuid'),
      'target entity base field' => $target_entity_type->getKey('id'),
      'field_name' => $field_name,
      'field table' => $table_mapping->getDedicatedDataTableName($field_storage),
      'field field' => $field_name . '_target_uuid',
      'join_extra' => [
        [
          'field' => 'deleted',
          'value' => 0,
          'numeric' => TRUE,
        ],
      ],
    ];
  }

  return $data;
}

/**
 * Implements hook_views_data_alter()
 */
function entity_reference_uuid_views_data_alter(array &$data) {

  // @see views_views_data() where it registers views data for the entity itself.
  foreach (\Drupal::entityTypeManager()->getDefinitions() as $entity_type_id => $entity_type) {
    // Only alter entity types exposed to Views.
    if ($entity_type->hasHandlerClass('views_data') && $entity_type->hasKey('uuid')) {

      // Add direct and reverse relationships for base fields.
      $base_field_definitions = \Drupal::entityManager()->getBaseFieldDefinitions($entity_type_id);
      foreach ($base_field_definitions as $base_field_name => $base_field_definition) {
        if (!empty($base_field_definition) && $base_field_definition->getType() == 'entity_reference_uuid') {
          /** @var \Drupal\Core\Field\BaseFieldDefinition $base_field_definition */
          entity_reference_uuid_views_data_helper($data, $entity_type, $base_field_definition);
        }
      }

      // Add an extra relationship if needed from the data table so that the
      // UUID can be found and used.
      // @todo - not clear if we actually need the relationship section here.
      if ($entity_type->getDataTable()) {
        $base_table = $entity_type->getBaseTable();
        $uuid_field = $entity_type->getKey('uuid');
        $base_field = $entity_type->getKey('id');
        $data[$entity_type->getDataTable()][$uuid_field] = array(
          'title' => t('Entity UUID Dummy'),
          'relationship' => [
            // Views name of the table being joined to from foo.
            'base' => $base_table,
            // Database field name in example_table for the join.
            'base field' => $base_field,
            // Real database field name.
            'field' => $uuid_field,
            // ID of relationship handler plugin to use.
            'id' => 'entity_standard_uuid',
            'label' => t('UUID reference'),
          ],
        );
      }
    }
  }
}

/**
 * Helper function for entity_reference_uuid_views_data()
 *
 * This is a separate function so that other field types extending
 * entity_reference_uuid can call the same code.
 *
 * @param $data
 * @param $entity_type
 * @param \Drupal\Core\Field\BaseFieldDefinition $base_field_definition
 *
 * @return void
 */
function entity_reference_uuid_views_data_helper(&$data, $entity_type, \Drupal\Core\Field\BaseFieldDefinition $base_field_definition) {
  $entity_manager = \Drupal::entityTypeManager();
  // The ID of the entity type the field is attached to.
  $entity_type_id = $entity_type->id();
  $storage = $entity_manager->getStorage($entity_type_id);
  $field_name = $base_field_definition->getName();

  $table_name = '';
  if ($table_mapping = $storage->getTableMapping([$base_field_definition])) {
    foreach ($table_mapping->getTableNames() as $table) {
      if (isset($data[$table][$field_name])) {
        $table_name = $table;
        break;
      }
    }
  }
  if (!$table_name) {
    return;
  }

  // Add a relationship to the target entity type.
  $target_entity_type_id = $base_field_definition->getItemDefinition()->getSetting('target_type');
  $target_entity_type = $entity_manager->getDefinition($target_entity_type_id);

  // The line below is technically wrong since the UUID is not copied to the
  // data table, but we use hook_views_data_alter() to make it appear to
  // be present.
  $target_base_table = $target_entity_type->getDataTable() ?: $target_entity_type->getBaseTable();

  // Provide a relationship for the entity type with the entity reference field.
  $args = [
    '@label' => $target_entity_type->getLabel(),
    '@field_name' => $field_name,
  ];

  $data[$table_name]['table']['join'][$target_base_table] = [
    'field' => 'target_uuid',
    'left_field' => 'uuid',
    'table' => $table_name,
  ];

  if ($target_entity_bundles = $base_field_definition->getTargetBundle()) {
    $target_entity_bundles = implode(', ', $target_entity_bundles);
  }
  else {
    $target_entity_bundles = 'none';
  }

  $data[$table_name][$field_name]['relationship'] = [
    'title' => t('@label referenced from @field_name', $args),
    'label' => t('@field_name: @label', $args),
    'group' => $entity_type->getLabel(),
    'help' => t('Appears in: @bundles.', ['@bundles' => $target_entity_bundles]),
    'id' => 'entity_standard_uuid',
    'base' => $target_base_table,
    'entity base table' => $target_entity_type->getBaseTable(),
    'entity uuid field' => $target_entity_type->getKey('uuid'),
    'entity type' => $target_entity_type_id,
    'base field' => $target_entity_type->getKey('id'),
    // 'relationship field' => $field_name . '_target_uuid',
    'relationship field' => $field_name,
  ];

  // Provide a reverse relationship for the entity type that is referenced by
  // the field.
  $args['@entity'] = $entity_type->getLabel();
  $args['@label'] = $target_entity_type->getLowercaseLabel();
  $pseudo_field_name = 'reverse__' . $entity_type_id . '__' . $field_name;
  $data[$target_base_table][$pseudo_field_name]['relationship'] = [
    'title' => t('@entity using @field_name', $args),
    'label' => t('@field_name', ['@field_name' => $field_name]),
    'group' => $target_entity_type->getLabel(),
    'help' => t('Relate each @entity with a @field_name set to the @label.', $args),
    'id' => 'entity_reverse_uuid',
    'base' => $entity_type->getDataTable() ?: $entity_type->getBaseTable(),
    'entity base table' => $entity_type->getBaseTable(),
    'entity_type' => $entity_type_id,
    'base field' => $entity_type->getKey('id'),
    'target base' => $target_base_table,
    'target entity base table' => $target_entity_type->getBaseTable(),
    'target entity uuid field' => $target_entity_type->getKey('uuid'),
    'target entity base field' => $target_entity_type->getKey('id'),
    'field_name' => $field_name,
    'field table' => $table_name,
    // 'field field' => $field_name . '_target_uuid',
    'field field' => $field_name,
  ];
}

/**
 * Implements hook_field_views_data_alter().
 *
 * @see views_field_default_views_data()
 */
function entity_reference_uuid_field_views_data_alter(array &$data, FieldStorageConfigInterface $field_storage) {

  // Views integration for entity reference uuid fields which reference taxonomy terms.
  // Adds a term relationship to the default field data.
  if ($field_storage->getType() == 'entity_reference_uuid' && $field_storage->getSetting('target_type') == 'taxonomy_term') {
    foreach ($data as $table_name => $table_data) {
      foreach ($table_data as $field_name => $field_data) {
        if (isset($field_data['filter']) && $field_name != 'delta') {
          $data[$table_name][$field_name]['filter']['id'] = 'entity_reference_uuid_taxonomy_index_uuid';
        }
      }
    }
  }

}
