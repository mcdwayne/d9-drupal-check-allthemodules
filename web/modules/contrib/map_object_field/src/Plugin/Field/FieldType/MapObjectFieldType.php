<?php
namespace Drupal\map_object_field\Plugin\Field\FieldType;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\map_object_field\Plugin\Field\TMapOptions;

/**
 * Plugin implementation of the 'Map object' field type.
 *
 * @FieldType(
 *   id = "map_object_field",
 *   label = @Translation("Map Object field"),
 *   description = @Translation("This field stores Map Object fields in the database."),
 *   default_widget = "map_object_field_default",
 *   default_formatter = "map_object_field_default"
 * )
 */
class MapObjectFieldType extends FieldItemBase {

  use TMapOptions;

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field) {
    return [
      'columns' => [
        'map_object_name' => [
          'type' => 'varchar',
          'length' => 128,
        ],
        'map_type' => [
          'type' => 'varchar',
          'length' => 20,
          'description' => 'terrain, hybrid...',
        ],
        'map_center_lat' => [
          'type' => 'float',
          'size' => 'big',
          'default' => 0.0,
        ],
        'map_center_lng' => [
          'type' => 'float',
          'size' => 'big',
          'default' => 0.0,
        ],
        'map_zoom' => [
          'type' => 'int',
          'length' => 10,
          'default' => 1,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $field_values = $this->getValue();
    $map_object_name = trim($this->get('map_object_name')->getValue());
    if (!empty($map_object_name)) {
      return FALSE;
    }
    if (isset($field_values['map_object_data'])) {
      if (!empty($field_values['map_object_data']) && $field_values['map_object_data'] != '[]') {
        return FALSE;
      }
      return TRUE;
    }
    else {
      /** @var \Drupal\map_object_field\MapObject\MapObjectService $map_object_service */
      $map_object_service = \Drupal::service('map_object.service');
      /** @var \Drupal\Core\Entity\EntityInterface $entity */
      $entity = $this->getEntity();
      $map_object_field_data = $map_object_service->getMapObjectsByFieldData(
        $entity->getEntityType()->id(),
        $entity->id(),
        $entity->getEntityType()
          ->isRevisionable() ? $entity->getRevisionId() : 1,
      // Delta.
        $this->getName()
      );
      if (!empty($map_object_field_data)) {
        return FALSE;
      }
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['map_object_name'] = DataDefinition::create('string')
      ->setLabel(t('Map Object Name'));

    $properties['map_type'] = DataDefinition::create('string')
      ->setLabel(t('Map Type'));

    $properties['map_center_lat'] = DataDefinition::create('string')
      ->setLabel(t('Latitude of the center of displayed map'));

    $properties['map_center_lng'] = DataDefinition::create('string')
      ->setLabel(t('Longtitude of the center of displayed map'));

    $properties['map_zoom'] = DataDefinition::create('string')
      ->setLabel(t('Map Zoom'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function postSave($update) {
    static $is_deleted = [];
    /** @var \Drupal\map_object_field\MapObject\MapObjectService $map_object_service */
    $map_object_service = \Drupal::service('map_object.service');
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $this->getParent()->getEntity();

    // Now we have to delete all map objects for revision.
    $key = "{$entity->getEntityTypeId()}:{$entity->id()}:{$entity->getRevisionId()}";

    if (!isset($is_deleted[$key])) {
      $map_object_service->deleteAllMapObjectsForEntity(
        $entity->getEntityTypeId(),
        $entity->id(),
        $entity->getRevisionId()
      );
      $is_deleted[$key] = TRUE;
    }

    $field_values = $this->getValue();
    $delta = $this->getName();
    $deserialized_data = Json::decode($field_values['map_object_data']);

    return $map_object_service->saveMapObjects(
      $entity->getEntityTypeId(),
      $entity->id(),
      $entity->getEntityType()
        ->isRevisionable() ? $entity->getRevisionId() : $entity->id(),
      $delta,
      $deserialized_data
    );
  }

  /**
   * {@inheritdoc}
   */
  public function delete() {
    /** @var \Drupal\map_object_field\MapObject\MapObjectService $map_object_service */
    $map_object_service = \Drupal::service('map_object.service');
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $this->getParent()->getEntity();
    $map_object_service->deleteAllMapObjectsForEntity(
      $entity->getEntityType()->id(),
      $entity->id()
    );
  }

  /**
   * {@inheritdoc}
   */
  public function deleteRevision() {
    /** @var \Drupal\map_object_field\MapObject\MapObjectService $map_object_service */
    $map_object_service = \Drupal::service('map_object.service');
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $this->getParent()->getEntity();
    $map_object_service->deleteAllMapObjectsForEntity(
      $entity->getEntityType()->id(),
      $entity->id(),
      $entity->getEntityType()
        ->isRevisionable() ? $entity->getRevisionId() : NULL
    );
  }

}
