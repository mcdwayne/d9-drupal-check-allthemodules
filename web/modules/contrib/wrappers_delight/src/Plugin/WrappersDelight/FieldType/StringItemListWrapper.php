<?php

namespace Drupal\wrappers_delight\Plugin\WrappersDelight\FieldType;

use Drupal\wrappers_delight\Annotation\WrappersDelight;
use Drupal\wrappers_delight\FieldItemListWrapper;

/**
 * @WrappersDelight(
 *   id = "field_list:string",
 *   type = WrappersDelight::TYPE_FIELD_LIST,
 *   field_type = "string",
 * )
 */
class StringItemListWrapper extends FieldItemListWrapper {

  /**
   * @return \Drupal\wrappers_delight\Plugin\WrappersDelight\FieldType\StringItemWrapper[]
   */
  public function toArray() {
    return parent::toArray();
  }

  /**
   * @return \Drupal\wrappers_delight\Plugin\WrappersDelight\FieldType\StringItemWrapper|NULL
   */
  public function first() {
    return parent::first();
  }
  /**
   * @param mixed $offset The offset to retrieve.
   *
   * @return \Drupal\wrappers_delight\Plugin\WrappersDelight\FieldType\StringItemWrapper[]
   */
  public function offsetGet($offset) {
    return parent::offsetGet($offset);
  }
  
}
