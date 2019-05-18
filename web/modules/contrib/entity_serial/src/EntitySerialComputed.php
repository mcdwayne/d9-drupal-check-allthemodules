<?php

namespace Drupal\entity_serial;

use Drupal\Core\Field\FieldItemList;
use Drupal\Core\TypedData\ComputedItemListTrait;

/**
 * Computes serial id for a bundle.
 */
class EntitySerialComputed extends FieldItemList {
  use ComputedItemListTrait;

  /**
   * Computes serial id.
   *
   * @todo refactor with EntitySerialFormatter
   */
  protected function computeValue() {
    $entity = $this->getEntity();
    $entityId = $entity->id();
    // @todo get from settings form
    $entityIdStart = 1;
    // @todo get from settings form
    $serialStart = 1;
    $delta = 0;
    if ($entityId < $entityIdStart) {
      $result = 0;
    }
    else {
      $query = \Drupal::database()->select('entity_serial', 'es')
        ->fields('es', ['entity_id'])
        ->condition('entity_type_id', $entity->getEntityTypeId())
        ->condition('entity_bundle', $entity->bundle())
        ->condition('entity_id', $entityIdStart, '>=')
        ->condition('entity_id', $entityId, '<');
      $amountEntities = $query->countQuery()->execute()->fetchField();
      $result = $serialStart + $amountEntities;
    }
    // Only one serial item by design.
    $this->list[$delta] = $this->createItem($delta, $result);
  }

}
