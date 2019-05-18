<?php

/**
 * @file
 * Provides views data for the entity_reference_layout module.
 *
 *  This is directly derived by the entity_reference_revisions module one.
 */

use Drupal\field\FieldStorageConfigInterface;

/**
 * Implements hook_field_views_data().
 */
function entity_reference_layout_field_views_data(FieldStorageConfigInterface $field_storage) {
  $data = views_field_default_views_data($field_storage);
  /* @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager */
  $entity_manager = \Drupal::entityTypeManager();
  foreach ($data as $table_name => $table_data) {
    try {
      // Add a relationship to the target entity type.
      $target_entity_type_id = $field_storage->getSetting('target_type');
      $target_entity_type = $entity_manager->getDefinition($target_entity_type_id);
      $entity_type_id = $field_storage->getTargetEntityTypeId();
      $entity_type = $entity_manager->getDefinition($entity_type_id);
      $target_base_table = $target_entity_type->getDataTable() ?: $target_entity_type->getBaseTable();
      $field_name = $field_storage->getName();

      // Provide a relationship for the entity type with the entity reference
      // revisions field.
      $args = [
        '@label' => $target_entity_type->getLabel(),
        '@field_name' => $field_name,
      ];
      $data[$table_name][$field_name]['relationship'] = [
        'title' => t('@label referenced from @field_name', $args),
        'label' => t('@field_name: @label', $args),
        'group' => $entity_type->getLabel(),
        'help' => t('Appears in: @bundles.', [
          '@bundles' => implode(', ', $field_storage->getBundles()),
        ]),
        'id' => 'standard',
        'base' => $target_base_table,
        'entity type' => $target_entity_type_id,
        'base field' => $target_entity_type->getKey('revision'),
        'relationship field' => $field_name . '_target_revision_id',
      ];

      // Provide a reverse relationship for the entity type that is referenced
      // by the field.
      $args['@entity'] = $entity_type->getLabel();
      $args['@label'] = $target_entity_type->getLowercaseLabel();
      $pseudo_field_name = 'reverse__' . $entity_type_id . '__' . $field_name;
      /** @var \Drupal\Core\Entity\Sql\SqlContentEntityStorage $storage */
      $storage = $entity_manager->getStorage($entity_type_id);
      /** @var \Drupal\Core\Entity\Sql\DefaultTableMapping $table_mapping */
      $table_mapping = $storage->getTableMapping();
      $data[$target_base_table][$pseudo_field_name]['relationship'] = [
        'title' => t('@entity using @field_name', $args),
        'label' => t('@field_name', ['@field_name' => $field_name]),
        'group' => $target_entity_type->getLabel(),
        'help' => t('Relate each @entity with a @field_name set to the @label.', $args),
        'id' => 'entity_reverse',
        'base' => $entity_type->getDataTable() ?: $entity_type->getBaseTable(),
        'entity_type' => $entity_type_id,
        'base field' => $entity_type->getKey('revision'),
        'field_name' => $field_name,
        'field table' => $table_mapping->getDedicatedDataTableName($field_storage),
        'field field' => $field_name . '_target_revision_id',
        'join_extra' => [
          [
            'field' => 'deleted',
            'value' => 0,
            'numeric' => TRUE,
          ],
        ],
      ];
    }
    catch (\Exception $e) {
      watchdog_exception('entity_reference_layout', $e);
    }
  }

  return $data;
}
