<?php

namespace Drupal\iots_device;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Defines the iots_device schema handler.
 */
class IotsDeviceStorageSchema extends SqlContentEntityStorageSchema {

  /**
   * {@inheritdoc}
   */
  protected function getEntitySchema(ContentEntityTypeInterface $entity_type, $reset = FALSE) {
    $schema = parent::getEntitySchema($entity_type, $reset = FALSE);
    $schema['iots_device']['indexes']['iots_device_field__secret'] = [
      'secret',
    ];
    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  protected function getSharedTableFieldSchema(FieldStorageDefinitionInterface $storage_definition, $table_name, array $column_mapping) {
    $schema = parent::getSharedTableFieldSchema($storage_definition, $table_name, $column_mapping);
    $field_name = $storage_definition->getName();
    switch ($field_name) {
      case 'secret':
        //$this->addSharedTableFieldIndex($storage_definition, $schema, TRUE);
        $this->addIndex('iots_device', 'secret', ['secret'], $schema);
        break;
    }
    return $schema;
  }

}
