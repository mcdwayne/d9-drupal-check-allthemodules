<?php

namespace Drupal\wrappers_delight\Plugin\WrappersDelight\FieldType;

use Drupal\wrappers_delight\Annotation\WrappersDelight;
use Drupal\wrappers_delight\FieldItemWrapper;

/**
 * @WrappersDelight(
 *   id = "field_type:decimal",
 *   type = WrappersDelight::TYPE_FIELD_TYPE,
 *   field_type = "decimal",
 * )
 */
class DecimalItemWrapper extends FieldItemWrapper {

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
