<?php

namespace Drupal\decoupled_auth;

use Drupal\user\UserStorageSchema;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Defines the decoupled user schema handler.
 */
class DecoupledAuthUserStorageSchema extends UserStorageSchema {

  /**
   * {@inheritdoc}
   */
  protected function getSharedTableFieldSchema(FieldStorageDefinitionInterface $storage_definition, $table_name, array $column_mapping) {
    $schema = parent::getSharedTableFieldSchema($storage_definition, $table_name, $column_mapping);
    $field_name = $storage_definition->getName();

    if ($table_name == 'users_field_data' && $field_name == 'name') {
      // Make the name field allow NULLs.
      $schema['fields'][$field_name]['not null'] = FALSE;
    }

    return $schema;
  }

}
