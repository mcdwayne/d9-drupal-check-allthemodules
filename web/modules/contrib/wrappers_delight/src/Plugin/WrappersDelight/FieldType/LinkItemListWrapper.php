<?php

namespace Drupal\wrappers_delight\Plugin\WrappersDelight\FieldType;

use Drupal\wrappers_delight\Annotation\WrappersDelight;
use Drupal\wrappers_delight\FieldItemListWrapper;

/**
 * @WrappersDelight(
 *   id = "field_list:link",
 *   type = WrappersDelight::TYPE_FIELD_LIST,
 *   field_type = "link",
 * )
 */
class LinkItemListWrapper extends FieldItemListWrapper {

  /**
   * @return \Drupal\wrappers_delight\Plugin\WrappersDelight\FieldType\LinkItemWrapper[]
   */
  public function toArray() {
    return parent::toArray();
  }

  /**
   * @return \Drupal\wrappers_delight\Plugin\WrappersDelight\FieldType\LinkItemWrapper|NULL
   */
  public function first() {
    return parent::first();
  }
  /**
   * @param mixed $offset The offset to retrieve.
   *
   * @return \Drupal\wrappers_delight\Plugin\WrappersDelight\FieldType\LinkItemWrapper[]
   */
  public function offsetGet($offset) {
    return parent::offsetGet($offset);
  }
  
}
