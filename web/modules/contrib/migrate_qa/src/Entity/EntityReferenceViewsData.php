<?php

namespace Drupal\migrate_qa\Entity;

use Drupal\views\EntityViewsData;

/**
 * Class EntityReferenceViewsData.
 *
 * Add reverse relationships for entity_reference fields.
 *
 * See core issue https://www.drupal.org/project/drupal/issues/2706431
 * "provide Views reverse relationships automatically for entity base fields".
 *
 * When that issue is resolved this class can be deprecated.
 */
class EntityReferenceViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $field_storage_definitions = $this->getFieldStorageDefinitions();

    foreach ($field_storage_definitions as $field_name => $field_storage) {
      // The code below only deals with the Entity reference field type.
      if ($field_storage->getType() != 'entity_reference') {
        continue;
      }
      // Problems occur with non-base fields, so only work on base fields.
      if (!$field_storage->isBaseField()) {
        continue;
      }

      $entity_manager = $this->entityManager;
      $entity_type_id = $field_storage->getTargetEntityTypeId();
      /** @var \Drupal\Core\Entity\Sql\DefaultTableMapping $table_mapping */
      $table_mapping = $entity_manager->getStorage($entity_type_id)->getTableMapping();
      $target_entity_type_id = $field_storage->getSetting('target_type');
      $target_entity_type = $entity_manager->getDefinition($target_entity_type_id);
      $entity_type = $entity_manager->getDefinition($entity_type_id);
      $target_base_table = $target_entity_type->getDataTable() ?: $target_entity_type->getBaseTable();
      $field_name = $field_storage->getName();

      // Provide a relationship for the entity type with the entity reference
      // field.
      $args = [
        '@label' => $target_entity_type->getLowercaseLabel(),
        '@field_name' => $field_name,
        '@entity' => $entity_type->getLabel(),
      ];

      // Provide a reverse relationship for the entity type that is referenced
      // by the field.
      $pseudo_field_name = 'reverse__' . $entity_type_id . '__' . $field_name;
      if ($table_mapping->requiresDedicatedTableStorage($field_storage)) {
        $data[$target_base_table][$pseudo_field_name] = [
          // There is a bridge table. Use the entity_reverse relationship
          // plugin.
          'relationship' => [
            'title' => t('@entity using @field_name', $args),
            'label' => t('@field_name', ['@field_name' => $field_name]),
            'help' => t('(Reverse Complex) Relate each @entity with a @field_name set to the @label.', $args),
            'group' => $target_entity_type->getLabel(),
            'id' => 'entity_reverse',
            'base' => $this->getViewsTableForEntityType($this->entityType),
            'entity_type' => $this->entityType->id(),
            'base field' => $this->entityType->getKey('id'),
            'field_name' => $field_name,
            'field table' => $table_mapping->getFieldTableName($field_name),
            'field field' => $table_mapping->getFieldColumnName($field_storage, 'target_id'),
          ],
        ];
      }
      else {
        // The data is on the base table. Use the standard relationship plugin.
        $data[$target_base_table][$pseudo_field_name] = [
          'relationship' => [
            'title' => t('@entity using @field_name', $args),
            'label' => t('@field_name', ['@field_name' => $field_name]),
            'help' => t('(Reverse Simple) Relate each @entity with a @field_name set to the @label.', $args),
            'group' => $target_entity_type->getLabel(),
            'id' => 'standard',
            'relationship field' => $target_entity_type->getKey('id'),
            'base' => $this->getViewsTableForEntityType($this->entityType),
            'base field' => $table_mapping->getFieldColumnName($field_storage, 'target_id'),
            'entity_type' => $this->entityType->id(),
          ],
        ];
      }
    }

    return $data;
  }
}
