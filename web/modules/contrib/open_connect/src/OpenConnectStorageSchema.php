<?php

namespace Drupal\open_connect;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Defines the open_connect schema handler.
 */
class OpenConnectStorageSchema extends SqlContentEntityStorageSchema {

  /**
   * {@inheritdoc}
   */
  protected function getEntitySchema(ContentEntityTypeInterface $entity_type, $reset = FALSE) {
    $schema = parent::getEntitySchema($entity_type, $reset);

    // Creates unique keys to guarantee the integrity of the entity and to make
    // the lookup in OpenConnectStorage::loadByOpenid() fast.
    $schema['open_connect']['unique keys'] += [
      'open_connect__provider_openid' => ['provider', 'openid'],
    ];
    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  protected function getSharedTableFieldSchema(FieldStorageDefinitionInterface $storage_definition, $table_name, array $column_mapping) {
    $schema = parent::getSharedTableFieldSchema($storage_definition, $table_name, $column_mapping);
    $field_name = $storage_definition->getName();

    if ($table_name == 'open_connect') {
      switch ($field_name) {
        case 'provider':
        case 'openid':
          // Improves the performance of the indexes defined
          // in getEntitySchema() by setting to non-nullable.
          $schema['fields'][$field_name]['not null'] = TRUE;
          break;
        case 'unionid':
          $this->addSharedTableFieldIndex($storage_definition, $schema);
          break;
      }
    }

    return $schema;
  }

}
