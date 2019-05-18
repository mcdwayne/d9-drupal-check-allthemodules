<?php

namespace Drupal\owntracks;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Class OwnTracksWaypointStorageSchema.
 *
 * @package Drupal\owntracks
 */
class OwnTracksWaypointStorageSchema extends SqlContentEntityStorageSchema {

  /**
   * {@inheritdoc}
   */
  protected function getEntitySchema(ContentEntityTypeInterface $entity_type, $reset = FALSE) {
    $schema = parent::getEntitySchema($entity_type, $reset);

    $schema['owntracks_waypoint']['indexes'] += [
      'owntracks_waypoint__uid_tst' => ['uid', 'tst'],
      'owntracks_waypoint__lat_lon' => ['lat', 'lon'],
    ];

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  protected function getSharedTableFieldSchema(FieldStorageDefinitionInterface $storage_definition, $table_name, array $column_mapping) {
    $schema = parent::getSharedTableFieldSchema($storage_definition, $table_name, $column_mapping);
    $field_name = $storage_definition->getName();

    if ($table_name == 'owntracks_waypoint') {
      switch ($field_name) {
        case 'rad':
          $this->addSharedTableFieldIndex($storage_definition, $schema);
          break;

        case 'description':
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
