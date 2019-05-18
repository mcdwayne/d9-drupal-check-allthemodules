<?php

namespace Drupal\external_entities\Plugin\Field;

use Drupal\Core\Field\FieldItemList;
use Drupal\Core\TypedData\ComputedItemListTrait;

/**
 * A computed annotation title field item list.
 */
class AnnotationTitleFieldItemList extends FieldItemList {

  use ComputedItemListTrait;

  /**
   * {@inheritdoc}
   */
  protected function computeValue() {
    $entity = $this->getEntity();
    $external_entity_type_id = \Drupal::entityQuery('external_entity_type')
      ->condition('annotation_entity_type_id', $entity->getEntityTypeId())
      ->condition('annotation_bundle_id', $entity->bundle())
      ->range(0, 1)
      ->execute();
    if (!empty($external_entity_type_id)) {
      /* @var \Drupal\external_entities\ExternalEntityTypeInterface $external_entity_type */
      $external_entity_type = \Drupal::entityTypeManager()
        ->getStorage('external_entity_type')
        ->load(array_shift($external_entity_type_id));
      $annotation_field_name = $external_entity_type->getAnnotationFieldName();
      /* @var \Drupal\external_entities\ExternalEntityInterface[] $external_entities */
      $external_entities = $entity->get($annotation_field_name)->referencedEntities();
      foreach ($external_entities as $delta => $external_entity) {
        $this->list[$delta] = $this->createItem($delta, $external_entity->label());
      }
    }
  }

}
