<?php

namespace Drupal\entity_reference_inline\Plugin\Field\FieldType;

/**
 * Defines an interface for the entity inline field item.
 */
interface EntityReferenceInlineItemInterface {

  /**
   * Checks directly if the parent entity has to be saved.
   *
   * This method will check if the item has to be saved only based on the
   * metadata i.e. it will not check for field value changes on the item entity.
   *
   * @param bool $include_current_item
   *   Whether to include the current item in the check for inconsistencies.
   *   Defaults to TRUE.
   *
   * @internal
   *
   * @return bool
   */
  public function needsSave($include_current_item = TRUE);

}
