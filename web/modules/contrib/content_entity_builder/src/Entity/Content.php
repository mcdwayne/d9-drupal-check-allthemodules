<?php

namespace Drupal\content_entity_builder\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\content_entity_builder\ContentInterface;

/**
 * Defines the Content entity.
 *
 * @ingroup content_entity_builder
 */
class Content extends ContentEntityBase implements ContentInterface {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entityType) {
    $fields = parent::baseFieldDefinitions($entityType);
    $content_type = \Drupal::entityManager()->getStorage('content_type')->load($entityType->id());
    if(empty($content_type)) {
      return $fields;
    }

    foreach ($content_type->getBaseFields() as $base_field) {
      $field_name = $base_field->getFieldName();
      $fields[$field_name] = $base_field->buildBaseFieldDefinition();
    }

    return $fields;
  }

}
