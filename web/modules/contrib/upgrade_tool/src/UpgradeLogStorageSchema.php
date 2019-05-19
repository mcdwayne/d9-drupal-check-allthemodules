<?php

namespace Drupal\upgrade_tool;

use Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Defines the upgrade_log schema handler.
 */
class UpgradeLogStorageSchema extends SqlContentEntityStorageSchema {

  /**
   * {@inheritdoc}
   */
  protected function getSharedTableFieldSchema(FieldStorageDefinitionInterface $storage_definition, $table_name, array $column_mapping) {
    $schema = parent::getSharedTableFieldSchema($storage_definition, $table_name, $column_mapping);
    $field_name = $storage_definition->getName();

    if ($table_name == 'upgrade_log') {
      if ($field_name == 'config_path' || $field_name == 'config_property') {
        $schema['fields'][$field_name]['not null'] = FALSE;
      }
    }

    return $schema;
  }

}
