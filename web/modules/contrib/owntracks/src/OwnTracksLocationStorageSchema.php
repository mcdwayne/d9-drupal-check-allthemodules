<?php

namespace Drupal\owntracks;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Class OwnTracksLocationStorageSchema.
 *
 * @package Drupal\owntracks
 */
class OwnTracksLocationStorageSchema extends SqlContentEntityStorageSchema {

  /**
   * {@inheritdoc}
   */
  protected function getEntitySchema(ContentEntityTypeInterface $entity_type, $reset = FALSE) {
    $schema = parent::getEntitySchema($entity_type, $reset);

    $schema['owntracks_location']['indexes'] += [
      'owntracks_location__uid_tid' => ['uid', 'tid'],
      'owntracks_location__lat_lon' => ['lat', 'lon'],
    ];

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  protected function getSharedTableFieldSchema(FieldStorageDefinitionInterface $storage_definition, $table_name, array $column_mapping) {
    $schema = parent::getSharedTableFieldSchema($storage_definition, $table_name, $column_mapping);
    $field_name = $storage_definition->getName();

    if ($table_name == 'owntracks_location') {
      switch ($field_name) {
        case 'acc':
        case 'tid':
          $this->addSharedTableFieldIndex($storage_definition, $schema);
          break;

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
