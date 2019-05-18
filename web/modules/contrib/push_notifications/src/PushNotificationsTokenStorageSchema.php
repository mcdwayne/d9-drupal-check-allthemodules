<?php

/**
 * @file
 * Contains \Drupal\push_notifications\Entity\PushNotificationsTokenStorageSchema.
 */

namespace Drupal\push_notifications;

use Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Defines the push_notifications_token schema handler.
 */
class PushNotificationsTokenStorageSchema extends SqlContentEntityStorageSchema {

  /**
   * {@inheritdoc}
   */
  protected function getSharedTableFieldSchema(FieldStorageDefinitionInterface $storage_definition, $table_name, array $column_mapping) {
    $schema = parent::getSharedTableFieldSchema($storage_definition, $table_name, $column_mapping);
    $field_name = $storage_definition->getName();

    if ($table_name == 'push_notifications_tokens') {
      switch ($field_name) {
        case 'created':
        case 'token':
        case 'network':
          $this->addSharedTableFieldIndex($storage_definition, $schema, TRUE);
          break;
      }
    }

    if ($table_name == 'push_notifications_tokens') {
      switch ($field_name) {
        case 'created':
        case 'token':
        case 'network':
          // Improves the performance of the indexes defined
          // in getEntitySchema().
          $schema['fields'][$field_name]['not null'] = TRUE;
          break;
      }
    }

    return $schema;
  }
}
