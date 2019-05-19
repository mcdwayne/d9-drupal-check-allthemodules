<?php

declare(strict_types = 1);

namespace Drupal\views_ef_fieldset;

use ArrayIterator;
use RecursiveIterator;

/**
 * Class ArrayDataItemIterator.
 */
class ArrayDataItemIterator extends ArrayIterator implements RecursiveIterator {

  /**
   * Get children.
   *
   * @return \DataItemIterator
   *   The children.
   */
  public function getChildren() {
    $item = $this->current();

    return new ArrayDataItemIterator($item['children']);
  }

  /**
   * Check if the item has children.
   *
   * @return bool
   *   True if it has children, false otherwise.
   */
  public function hasChildren() {
    $item = $this->current();

    return !empty($item['children']);
  }

}
