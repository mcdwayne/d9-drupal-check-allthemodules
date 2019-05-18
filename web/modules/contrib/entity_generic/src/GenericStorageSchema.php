<?php

namespace Drupal\entity_generic;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Defines the generic entity schema handler.
 */
class GenericStorageSchema extends SqlContentEntityStorageSchema {

  /**
   * {@inheritdoc}
   */
  protected function getEntitySchema(ContentEntityTypeInterface $entity_type, $reset = FALSE) {
    $schema = parent::getEntitySchema($entity_type, $reset);

    // @TODO: review this in future
    // Here we can add extra changes to the entity schema.

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  protected function getSharedTableFieldSchema(FieldStorageDefinitionInterface $storage_definition, $table_name, array $column_mapping) {
    $schema = parent::getSharedTableFieldSchema($storage_definition, $table_name, $column_mapping);
    $field_name = $storage_definition->getName();

    if ($table_name == $this->entityType->getDataTable()) {
      if (
        $field_name == $this->entityType->getKey('archived')
        || $field_name == $this->entityType->getKey('flag_deleted')
        || $field_name == $this->entityType->getKey('dummy')
      ) {
        $schema['fields'][$field_name]['not null'] = TRUE;
        $schema['fields'][$field_name]['default'] = 0;
      }

      if (
        $field_name == $this->entityType->getKey('archived')
        || $field_name == $this->entityType->getKey('flag_deleted')
        || $field_name == $this->entityType->getKey('dummy')
      ) {
        $this->addSharedTableFieldIndex($storage_definition, $schema, TRUE);
      }

    }

    return $schema;
  }

}
