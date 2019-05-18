<?php

/**
 * @file
 * Contains \Drupal\hookalyzer\Diff\Iterator\CompareIterator.
 */

namespace Drupal\hookalyzer\Diff\Iterator;

/**
 * Iterates over a pair of iterators and returns comparisons on their values.
 */
class CompareIterator implements \Iterator {

  protected $left;
  protected $right;
  protected $compare;

  function __construct(\Iterator $left, ComparableIterator $right) {
    $this->left = $left;
    $this->right = $right;
  }

  public function rewind() {
    $this->left->rewind();
    $this->right->rewind();
    $this->right->seek($this->left->key());
  }

  public function next() {
    $this->left->next();
    if ($this->left->valid()) {
      $this->right->seek($this->left->key());
    }
    else {
      $keys = $this->right->remainingKeys();
      $this->right->seek(array_shift($keys));
    }
  }

  public function valid() {
    return $this->left->valid() || $this->right->valid();
  }

  public function key() {
    return $this->left->key() ?: $this->right->key();
  }

  public function current() {
    return array($this->left->current(), $this->right->current());
  }
}