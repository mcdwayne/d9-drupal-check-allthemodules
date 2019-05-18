<?php

namespace Drupal\conflict;

use Drupal\Core\Field\FieldItemListInterface;

interface FieldComparatorInterface {

  /**
   * The identifier to be used by the plugin annotation and hasChanged().
   *
   * It specifies that a field comparator applies to all values of the key for
   * which it has been set as a value.
   */
  const APPLIES_TO_ALL = '*';

  /**
   * Checks whether the field items have changed.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $field_item_list_a
   *   A field item list.
   * @param \Drupal\Core\Field\FieldItemListInterface $field_item_list_b
   *   Another field item list.
   * @param string $langcode
   *   (optional) The language code of the entity translation being checked.
   * @param  string $entity_type_id
   *   (optional) The entity type ID.
   * @param string $bundle
   *   (optional) The entity bundle.
   * @param string $field_type
   *   (optional) The field type.
   * @param string $field_name
   *   (optional) The field name.
   *
   * @return bool
   *   TRUE, if both field item lists are equal, FALSE otherwise.
   */
  public function hasChanged(FieldItemListInterface $field_item_list_a, FieldItemListInterface $field_item_list_b, $langcode = NULL, $entity_type_id = NULL, $bundle = NULL, $field_type = NULL, $field_name = NULL);

}
