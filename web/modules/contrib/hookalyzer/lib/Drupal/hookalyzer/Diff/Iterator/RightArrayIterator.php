<?php

/**
 * @file
 * Contains \Drupal\hookalyzer\Diff\Iterator\RightArrayIterator.
 */

namespace Drupal\hookalyzer\Diff\Iterator;

/**
 * An iterator for the passively iterated (typically right) side of comparisons.
 */
class RightArrayIterator implements ComparableIterator {

  protected $arr;
  protected $keys;
  protected $current;
  protected $key;
  protected $valid = FALSE;

  public function __construct(array $value) {
    $this->arr = $value;
  }

  public function remainingKeys() {
    return array_diff(array_keys($this->arr), $this->keys);
  }

  public function current() {
    return $this->valid ? $this->current : NULL;
  }

  public function next() {}

  public function key() {
    return $this->valid ? $this->key : NULL;
  }

  public function valid() {
    return count($this->arr) > count($this->keys);
  }

  public function rewind() {
    // Reset the list of visited keys
    $this->keys = array();
  }

  public function seek($position) {
    if (array_key_exists($position, $this->arr)) {
      $this->current = $this->arr[$position];
      $this->valid = TRUE;
      $this->key = $position;
      $this->keys[] = $position;
    }
    else {
      $this->valid = FALSE;
    }
  }
}