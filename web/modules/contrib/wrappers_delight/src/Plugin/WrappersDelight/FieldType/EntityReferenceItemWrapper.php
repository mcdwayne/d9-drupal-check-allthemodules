<?php

namespace Drupal\wrappers_delight\Plugin\WrappersDelight\FieldType;

use Drupal\wrappers_delight\Annotation\WrappersDelight;
use Drupal\wrappers_delight\FieldItemWrapper;

/**
 * @WrappersDelight(
 *   id = "field_type:entity_reference",
 *   type = WrappersDelight::TYPE_FIELD_TYPE,
 *   field_type = "entity_reference",
 * )
 */
class EntityReferenceItemWrapper extends FieldItemWrapper {

  /**
   * @return \Drupal\wrappers_delight\WrapperBase
   */
  public function getValue() {
    if (!$this->item->isEmpty()) {
      return \Drupal::service('plugin.manager.wrappers_delight')->wrapEntity($this->item->entity);
    }
    return NULL;
  }

  /**
   * @return int|NULL
   */
  public function getTargetId() {
    if (!$this->item->isEmpty()) {
      return $this->item->target_id;
    }
    return NULL;
  }

}
