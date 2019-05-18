<?php

namespace Drupal\entity_split;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Defines the schema handler for entity_split entities.
 */
class EntitySplitStorageSchema extends SqlContentEntityStorageSchema {

  /**
   * {@inheritdoc}
   */
  protected function getEntitySchema(ContentEntityTypeInterface $entity_type, $reset = FALSE) {
    $schema = parent::getEntitySchema($entity_type, $reset);

    $schema['entity_split_field_data']['indexes'] += [
      'entity_split__type_entity' => [
        'type',
        'entity_id',
        'entity_type',
      ],
      'entity_split__entity_langcode' => [
        'entity_id',
        'entity_type',
        'langcode',
        'type',
      ],
    ];

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  protected function getSharedTableFieldSchema(FieldStorageDefinitionInterface $storage_definition, $table_name, array $column_mapping) {
    $schema = parent::getSharedTableFieldSchema($storage_definition, $table_name, $column_mapping);

    if ($table_name == 'entity_split_field_data') {
      // Remove unneeded indexes.
      unset($schema['indexes']['entity_split_field__type__target_id']);
      unset($schema['indexes']['entity_split_field__entity_id__target_id']);

      $field_name = $storage_definition->getName();

      switch ($field_name) {
        case 'entity_type':
        case 'entity_id':
          // Improves the performance of the indexes
          // defined in getEntitySchema().
          $schema['fields'][$field_name]['not null'] = TRUE;
          break;

        default:
          break;
      }
    }

    return $schema;
  }

}
