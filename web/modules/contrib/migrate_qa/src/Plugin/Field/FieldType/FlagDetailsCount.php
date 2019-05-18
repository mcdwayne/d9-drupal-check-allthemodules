<?php

namespace Drupal\migrate_qa\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemList;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\TypedData\ComputedItemListTrait;

/**
 * Computed field showing the number of items in the details field.
 */
class FlagDetailsCount extends FieldItemList implements FieldItemListInterface {

  use ComputedItemListTrait;

  /**
   * {@inheritdoc}
   */
  protected function computeValue() {
    $count = $this->getParent()->getValue()->details->count();
    $this->list[0] = $this->createItem(0, $count);
  }

}
