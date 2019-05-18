<?php

/**
 * @file
 * Contains \Drupal\hookalyzer\Diff\Iterator\EmptyComparableIterator.
 */

namespace Drupal\hookalyzer\Diff\Iterator;

/**
 * TODO Add class description.
 */
class EmptyComparableIterator implements ComparableIterator {

  public function remainingKeys() {
    return array();
  }

  public function seek($position) {}

  public function current() {
    return NULL;
  }

  public function next() {}

  public function key() {
    return NULL;
  }

  public function valid() {
    return FALSE;
  }

  public function rewind() {}

}