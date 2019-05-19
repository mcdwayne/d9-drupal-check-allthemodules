<?php

namespace Drupal\wrappers_delight\Plugin\WrappersDelight\FieldType;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\wrappers_delight\Annotation\WrappersDelight;
use Drupal\wrappers_delight\FieldItemWrapper;

/**
 * @WrappersDelight(
 *   id = "field_type:datetime",
 *   type = WrappersDelight::TYPE_FIELD_TYPE,
 *   field_type = "datetime",
 * )
 */
class DateTimeItemWrapper extends FieldItemWrapper {

  /**
   * @return \Drupal\Core\Datetime\DrupalDateTime
   */
  public function getValue() {
    if (!$this->item->isEmpty()) {
      return new DrupalDateTime($this->item->getValue()['value'], new \DateTimeZone('UTC'));
    }
    return NULL;
  }

}
