<?php

namespace Drupal\entity_reference_inline\Plugin\Field\FieldType;

use Drupal\Core\Field\EntityReferenceFieldItemList;

/**
 * Defines a item list class for entity reference fields.
 */
class EntityReferenceInlineFieldItemList extends EntityReferenceFieldItemList implements EntityReferenceInlineFieldItemListInterface {

  use FieldItemListCommonMethodsTrait;

}
