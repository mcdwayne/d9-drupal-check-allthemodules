<?php

namespace Drupal\workflow_participants;

use Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Storage schema for workflow_participants entity.
 */
class WorkflowParticipantsStorageSchema extends SqlContentEntityStorageSchema {

  /**
   * {@inheritdoc}
   */
  protected function getSharedTableFieldSchema(FieldStorageDefinitionInterface $storage_definition, $table_name, array $column_mapping) {
    $schema = parent::getSharedTableFieldSchema($storage_definition, $table_name, $column_mapping);
    $field_name = $storage_definition->getName();

    if ($table_name === 'workflow_participants') {
      switch ($field_name) {
        case 'moderated_entity':
          $name = $storage_definition->getName();
          $real_key = $this->getFieldSchemaIdentifierName($storage_definition->getTargetEntityTypeId(), $name);
          $schema['unique keys'][$real_key] = ["{$name}__target_id", "{$name}__target_type"];
          $schema['fields']["{$name}__target_id"]['not null'] = TRUE;
          $schema['fields']["{$name}__target_type"]['not null'] = TRUE;
          break;
      }
    }

    return $schema;
  }

}
