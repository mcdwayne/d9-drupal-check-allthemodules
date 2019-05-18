<?php

namespace Drupal\conflict;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Field\FieldItemListInterface;

interface FieldComparatorManagerInterface extends PluginManagerInterface {

  /**
   * Compares two field item lists.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $items_a
   *   The first field item list to compare.
   * @param \Drupal\Core\Field\FieldItemListInterface $items_a
   *   The second field item list to compare.
   * @param string $langcode
   *   The language code of the entity translation being checked.
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $bundle
   *   The entity bundle ID.
   * @param string $field_type
   *   The field type.
   * @param string $field_name
   *   The field name.
   *
   * @return bool
   *   TRUE, if the items have changed, FALSE otherwise.
   *
   * @throws \Exception
   *   An exception will be thrown if for some reason even the default field
   *   comparator has not been added to the field comparators list.
   */
  public function hasChanged(FieldItemListInterface $items_a, FieldItemListInterface $items_b, $langcode, $entity_type_id, $bundle, $field_type, $field_name);

}
