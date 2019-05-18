<?php

/**
 * @file
 * Views hooks for entity_hierarchy_views.
 */

/**
 * Implements hook_views_data().
 */
function entity_hierarchy_views_data() {
  // Adds the relationship from the entity-base table to any tree tables that
  // exist by way of entity reference hierarchy fields.
  $data = [];
  /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager */
  $entityTypeManager = \Drupal::service('entity_type.manager');
  /** @var \Drupal\Core\Entity\EntityFieldManager $fieldManager */
  $fieldManager = \Drupal::service('entity_field.manager');
  foreach ($fieldManager->getFieldMapByFieldType('entity_reference_hierarchy') as $entity_type_id => $fields) {
    /** @var \Drupal\Core\Entity\EntityTypeInterface $entity_type */
    $entity_type = $entityTypeManager->getDefinition($entity_type_id);
    if (!$entityTypeManager->hasHandler($entity_type_id, 'views_data')) {
      // If there is no existing views integration, we cannot do anything here.
      continue;
    }
    /** @var \Drupal\views\EntityViewsDataInterface $views_handler */
    $views_handler = $entityTypeManager->getHandler($entity_type_id, 'views_data');
    foreach ($fields as $field_name => $field_info) {
      $table_name = \Drupal::service('entity_hierarchy.nested_set_storage_factory')
        ->getTableName($field_name, $entity_type_id, FALSE);
      $sample_bundle = reset($field_info['bundles']);
      /** @var Drupal\Core\Field\FieldDefinitionInterface $sample_field */
      $sample_field = $fieldManager->getFieldDefinitions($entity_type_id, $sample_bundle)[$field_name];
      $data[$table_name]['table']['group'] = t('Entity hierarchy: @entity_type - @field_name', [
        '@entity_type' => $entity_type->getLabel(),
        '@field_name' => $sample_field->getLabel(),
      ]);
      $base_table = $views_handler->getViewsTableForEntityType($entity_type);

      // Relationship to the entity base table.
      $data[$table_name]['table']['join'] = [
        $base_table => [
          'left_field' => $entity_type->getKey('id'),
          'field' => 'id',
        ],
      ];
      if ($has_revisions = $entity_type->hasKey('revision')) {
        $revision_key = $entity_type->getKey('revision');
        $data[$table_name]['table']['join'][$base_table]['left_field'] = $revision_key;
        $data[$table_name]['table']['join'][$base_table]['field'] = 'revision_id';
      }

      // @see \PNX\NestedSet\Storage\DbalNestedSetSchema::create()
      // Sort for left position in tree.
      $data[$table_name]['left_pos'] = [
        'title' => t('Hierarchy order'),
        'help' => t('Sort in hierarchy order'),
        'sort' => [
          'id' => 'standard',
        ],
      ];
      // Contextual filter for filtering to children of a given parent.
      $data[$table_name]['is_child'] = [
        'title' => t('Hierarchy: Is child of'),
        'help' => t('Limit to children of given entity'),
        'real field' => 'left_pos',
        'argument' => [
          'id' => $has_revisions ? 'entity_hierarchy_argument_is_child_of_entity_revision' : 'entity_hierarchy_argument_is_child_of_entity',
        ],
      ];
      // Contextual filter for filtering to parent of a given child.
      $data[$table_name]['is_parent'] = [
        'title' => t('Hierarchy: Is Parent of'),
        'help' => t('Limit to parent of given entity'),
        'real field' => 'left_pos',
        'argument' => [
          'id' => $has_revisions ? 'entity_hierarchy_argument_is_parent_of_entity_revision' : 'entity_hierarchy_argument_is_parent_of_entity',
        ],
      ];
      // Contextual filter for filtering to sibling of a given child.
      $data[$table_name]['is_sibling'] = [
        'title' => t('Hierarchy: Is Sibling of'),
        'help' => t('Limit to sibling of given entity'),
        'real field' => 'left_pos',
        'argument' => [
          'id' => $has_revisions ? 'entity_hierarchy_argument_is_sibling_of_entity_revision' : 'entity_hierarchy_argument_is_sibling_of_entity',
        ],
      ];
      // Sorting and filtering on depth.
      $data[$table_name]['depth'] = [
        'title' => t('Hierarchy depth'),
        'help' => t('Depth in hierarchy'),
        'sort' => [
          'id' => 'standard',
        ],
        'filter' => [
          'id' => 'numeric',
        ],
        'argument' => [
          'id' => 'standard',
        ],
      ];
      // Additional relationship from base table to field.
      $data[$base_table]['tree'] = [
        'title' => t('Entity hierarchy'),
        'help' => t('The hierarchy information'),
        'real field' => $has_revisions ?: $entity_type->getKey('id'),
        'group' => t('Entity hierarchy'),
        'relationship' => [
          'title' => 'Entity Hierarchy',
          'help' => 'Relate to hierarchy information',
          'id' => 'standard',
          'base' => $table_name,
          'base field' => $has_revisions ? 'revision_id' : 'id',
          'field' => $has_revisions ?: $entity_type->getKey('id'),
        ],
      ];
    }
  }

  return $data;
}
