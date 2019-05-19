<?php

namespace Drupal\wrappers_delight\Plugin\WrappersDelight\FieldType;

use Drupal\wrappers_delight\Annotation\WrappersDelight;
use Drupal\wrappers_delight\FieldItemWrapper;

/**
 * @WrappersDelight(
 *   id = "field_type:float",
 *   type = WrappersDelight::TYPE_FIELD_TYPE,
 *   field_type = "float",
 * )
 */
class FloatItemWrapper extends FieldItemWrapper {

  /**
   * @return float
   */
  public function getValue() {
    if (!$this->item->isEmpty()) {
      return (float) $this->item->getValue()['value'];
    }
    return NULL;
  }

}
