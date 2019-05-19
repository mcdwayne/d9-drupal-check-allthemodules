<?php

namespace Drupal\wrappers_delight\Plugin\WrappersDelight\FieldType;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\wrappers_delight\Annotation\WrappersDelight;
use Drupal\wrappers_delight\FieldItemWrapper;

/**
 * @WrappersDelight(
 *   id = "field_type:created",
 *   type = WrappersDelight::TYPE_FIELD_TYPE,
 *   field_type = "created",
 * )
 */
class CreatedItemWrapper extends FieldItemWrapper {

  /**
   * @return \Drupal\Core\Datetime\DrupalDateTime
   */
  public function getValue() {
    if (!$this->item->isEmpty()) {
      return DrupalDateTime::createFromTimestamp($this->item->getValue()['value']);
    }
    return NULL;
  }

}
