<?php

namespace Drupal\entity_reference_inline\Plugin\Field\FieldType;

use Drupal\Core\Field\EntityReferenceFieldItemListInterface;

/**
 * Interface for entity reference inline lists of field items.
 */
interface EntityReferenceInlineFieldItemListInterface extends EntityReferenceFieldItemListInterface {

  /**
   * Checks directly if the item list has to be saved.
   *
   * This method will check if the item list has to be saved only based on the
   * metadata i.e. it will not check for field value changes on the item list
   * entities.
   *
   * @internal
   *
   * @return bool
   */
  public function needsSave();

}
