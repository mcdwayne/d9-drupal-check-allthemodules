<?php

namespace Drupal\entity_tools;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\field\Entity\FieldConfig;

/**
 * Class FieldTools.
 *
 * @package Drupal\entity_tools
 */
class FieldTools {

  /**
   * Get a field name from an entity.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   Fieldable entity.
   * @param string $field_type
   *   Field type.
   *
   * @return null|string
   *   Field name.
   */
  public static function getFieldNameFromType(FieldableEntityInterface $entity, $field_type) {
    $result = NULL;
    if (!empty($field_type)) {
      $fieldDefinitions = $entity->getFieldDefinitions();
      foreach ($fieldDefinitions as $fieldDefinition) {
        if ($fieldDefinition instanceof FieldConfig) {
          if ($fieldDefinition->getType() === $field_type) {
            $result = $fieldDefinition->getName();
          }
        }
      }
    }
    return $result;
  }

  /**
   *
   */
  public static function getFileUri(FieldableEntityInterface $entity, $field_name = 'field_image') {
    $result = NULL;
    if ($entity->hasField($field_name)) {
      $result = $entity->get($field_name)->entity->getFileUri();
    }
    return $result;
  }

  /**
   *
   */
  public static function getStyledImageUrl(FieldableEntityInterface $entity, $field_name = 'field_image') {
    // @todo implement
  }

  /**
   *
   */
  public static function getFirstValue(FieldableEntityInterface $entity, $field_name, $property = NULL) {
    // @todo implement
  }

  /**
   *
   */
  public static function mergeValues(array $entities, $field_name, $property) {
    // @todo implement
  }

  /**
   *
   */
  public static function loadReferences(FieldableEntityInterface $entity, $field_name) {
    $result = $entity->get($field_name)->referencedEntities();
    return $result;
  }

}
