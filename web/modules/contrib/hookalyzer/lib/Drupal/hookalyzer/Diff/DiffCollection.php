<?php

/**
 * @file
 * Contains \Drupal\hookalyzer\Diff\DiffCollection.
 */

namespace Drupal\hookalyzer\Diff;

/**
 * TODO Add class description.
 */
class DiffCollection implements \IteratorAggregate {

  const OBJECT_COLLECTION = 0;
  const ARRAY_COLLECTION = 1;

  protected $name;
  protected $type;
  protected $diffs = array();

  public function __construct($name, $type = self::ARRAY_COLLECTION) {
    $this->name = $name;
    $this->type = $type;
  }

  public function addDiff($id, DiffInterface $diff) {
    $this->diffs[$id] = $diff;
  }

  public function getIterator() {
    return new \ArrayIterator($this->diffs);
  }

  public function getName() {
    return $this->name;
  }

}