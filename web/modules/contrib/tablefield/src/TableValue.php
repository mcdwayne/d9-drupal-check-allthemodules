<?php

namespace Drupal\tablefield;

use Drupal\Core\TypedData\TypedData;

/**
 * A computed property for Search API indexing.
 */
class TableValue extends TypedData {

  /**
   * {@inheritdoc}
   */
  public function getValue() {
    /** @var \Drupal\tablefield\Plugin\Field\FieldType\TablefieldItem $item */
    $item = $this->getParent();
    $value = '';
    if (isset($item->value)) {
      foreach ($item->value as $row) {
        $value .= implode(' ', $row) . ' ';
      }
      $value = trim($value);
    }
    return $value;
  }

}
