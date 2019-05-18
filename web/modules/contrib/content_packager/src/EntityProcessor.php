<?php

namespace Drupal\content_packager;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\FieldableEntityInterface;

/**
 * Helper to derive some URIs from entity refs.
 *
 * @package Drupal\content_packager
 */
class EntityProcessor {

  /**
   * Dig up URIs from fields on an entity (and referenced entities).
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to process.
   * @param array $options
   *   The image styles we want to generate URIs for and the fields we want
   *   to ignore.
   *      ['image_styles' => [], 'field_blacklist' => []].
   *
   * @return array
   *   The URIs to package up.
   */
  public static function processEntity(EntityInterface $entity, array $options) {
    if (content_packager_is_processed($entity)) {
      return [];
    }

    $uris = [];

    $field_blacklist = $options['field_blacklist'];

    $accepted_types = content_packager_accepted_field_types();
    $fieldable = $entity->getEntityType()->entityClassImplements(FieldableEntityInterface::class);

    if ($fieldable !== TRUE) {
      return $uris;
    }
    /** @var \Drupal\Core\Entity\FieldableEntityInterface $entity */
    $fields = $entity->getFields();

    foreach ($fields as $field) {
      $def = $field->getFieldDefinition();

      $type = $def->getType();
      if (!in_array($type, $accepted_types)) {
        continue;
      }

      $field_id = "{$entity->getEntityTypeId()}.{$entity->bundle()}.{$field->getName()}";
      if (in_array($field_id, $field_blacklist)) {
        continue;
      }

      content_packager_add_processed($entity);

      switch ($type) {
        case 'file':
          /** @var \Drupal\file\Plugin\Field\FieldType\FileFieldItemList $field */
          $uris = array_merge(FieldProcessor::processFileField($field), $uris);
          break;

        case 'image':
          /** @var \Drupal\file\Plugin\Field\FieldType\FileFieldItemList $field */
          $uris = array_merge(FieldProcessor::processImageField($field, $options), $uris);
          break;

        case 'entity_reference':
          /** @var \Drupal\Core\Field\EntityReferenceFieldItemList $field */
          $uris = array_merge(FieldProcessor::processEntityRefField($field, $options), $uris);
          break;
      }
    }

    return $uris;
  }

}
