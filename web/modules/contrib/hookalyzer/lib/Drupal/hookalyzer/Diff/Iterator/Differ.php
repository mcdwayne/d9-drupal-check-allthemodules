<?php

/**
 * @file
 * Contains \Drupal\hookalyzer\Diff\Iterator\Differ.
 */

namespace Drupal\hookalyzer\Diff\Iterator;

use Drupal\hookalyzer\Diff\Diff;

/**
 * TODO Add class description.
 */
class Differ implements \OuterIterator {
  /**
   * @var \Iterator
   */
  protected $dataIterator;

  protected $first = TRUE;

  /**
   * @var CompareIterator
   */
  protected $compareIterator;

  /**
   * The cache of iterated values.
   *
   * @var array
   */
  protected $cache = array();

  public function __construct(\Iterator $it) {
    $this->dataIterator = $it;
  }

  public function getInnerIterator() {
    return $this->compareIterator;
  }

  public function setNextIterator(\Iterator $it) {
    $this->dataIterator = $it;
  }

  public function rewind() {
    $this->compareIterator = new CompareIterator($this->dataIterator,
      $this->first ? new EmptyComparableIterator() : new RightArrayIterator($this->cache));
    $this->compareIterator->rewind(); // Ensures they start on the same key

    $this->first = FALSE;
    $this->cache = array();
  }

  public function next() {
    $this->getInnerIterator()->next();
  }

  public function current() {
    if ($this->dataIterator->valid()) {
      $this->cache[$this->dataIterator->key()] = $this->dataIterator->current();
    }

    $values = $this->getInnerIterator()->current();
    return Diff::diff($values[1], $values[0]);
  }

  public function key() {
    return $this->getInnerIterator()->key();
  }

  public function valid() {
    return $this->getInnerIterator()->valid();
  }
}