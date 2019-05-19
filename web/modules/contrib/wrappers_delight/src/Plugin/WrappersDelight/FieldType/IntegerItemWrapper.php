<?php

namespace Drupal\wrappers_delight\Plugin\WrappersDelight\FieldType;

use Drupal\wrappers_delight\Annotation\WrappersDelight;
use Drupal\wrappers_delight\FieldItemWrapper;

/**
 * @WrappersDelight(
 *   id = "field_type:integer",
 *   type = WrappersDelight::TYPE_FIELD_TYPE,
 *   field_type = "integer",
 * )
 */
class IntegerItemWrapper extends FieldItemWrapper {

  /**
   * @return int
   */
  public function getValue() {
    if (!$this->item->isEmpty()) {
      return (int) $this->item->getValue()['value'];
    }
    return NULL;
  }

}
