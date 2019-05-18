<?php

namespace Drupal\conflict\Plugin\Conflict\FieldComparator;

use Drupal\conflict\FieldComparatorInterface;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Default field comparator plugin implementation covering all fields.
 *
 * @FieldComparator(
 *   id = "conflict_field_comparator_default",
 *   entity_type_id = "*",
 *   bundle = "*",
 *   field_type = "*",
 *   field_name = "*",
 * )
 */
class FieldComparatorDefault implements FieldComparatorInterface {

  /**
   * {@inheritdoc}
   */
  public function hasChanged(FieldItemListInterface $field_item_list_a, FieldItemListInterface $field_item_list_b, $langcode = NULL, $entity_type_id = NULL, $bundle = NULL, $field_type = NULL, $field_name = NULL) {
    $result = !$field_item_list_a->equals($field_item_list_b);
    return $result;
  }

}
