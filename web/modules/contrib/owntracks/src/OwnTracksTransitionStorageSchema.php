<?php

namespace Drupal\owntracks;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Class OwnTracksTransitionStorageSchema.
 *
 * @package Drupal\owntracks
 */
class OwnTracksTransitionStorageSchema extends SqlContentEntityStorageSchema {

  /**
   * {@inheritdoc}
   */
  protected function getEntitySchema(ContentEntityTypeInterface $entity_type, $reset = FALSE) {
    $schema = parent::getEntitySchema($entity_type, $reset);

    $schema['owntracks_transition']['indexes'] += [
      'owntracks_transition__uid_tid' => ['uid', 'tid'],
      'owntracks_transition__lat_lon' => ['lat', 'lon'],
    ];

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  protected function getSharedTableFieldSchema(FieldStorageDefinitionInterface $storage_definition, $table_name, array $column_mapping) {
    $schema = parent::getSharedTableFieldSchema($storage_definition, $table_name, $column_mapping);
    $field_name = $storage_definition->getName();

    if ($table_name == 'owntracks_transition') {
      switch ($field_name) {
        case 'tid':
          $this->addSharedTableFieldIndex($storage_definition, $schema);
          break;

        case 'acc':
        case 'lat':
        case 'lon':
        case 'tst':
          $this->addSharedTableFieldIndex($storage_definition, $schema, TRUE);
          break;
      }
    }

    return $schema;
  }

}
