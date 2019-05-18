<?php

namespace Drupal\drupal_inquicker\Utilities;

use ArrayIterator;
use Countable;
use Drupal\drupal_inquicker\traits\CommonUtilities;
use Drupal\drupal_inquicker\traits\DependencyInjection;
use IteratorAggregate;

/**
 * Collections can be used as more structured wrappers around arrays.
 */
abstract class Collection implements IteratorAggregate, Countable {

  use CommonUtilities;
  use DependencyInjection;

  /**
   * Constructor.
   */
  public function __construct() {
    $this->items = [];
  }

  /**
   * Add items to the collection.
   *
   * @param array $items
   *   Array of items to add our collection.
   *
   * @throws \Exception
   */
  public function add(array $items) {
    foreach ($items as $item) {
      $this->validate($item);
      $this->items[] = $item;
    }
  }

  /**
   * Count items in the collection.
   *
   * @return int
   *   Number of items in the collection.
   */
  public function count() : int {
    return count($this->items);
  }

  /**
   * Add items to a new collection based on a filter.
   *
   * @param Collection $new
   *   A new collection to which to add items.
   * @param callable $callback
   *   A callback which will return TRUE for each item to add.
   */
  public function filter(Collection $new, callable $callback) {
    foreach ($this as $item) {
      if ($callback($item)) {
        $new->add([$item]);
      }
    }
    return $new;
  }

  /**
   * Get an iterator for this collection.
   *
   * @return ArrayIterator
   *   An array iterator.
   */
  public function getIterator() {
    return new ArrayIterator($this->items);
  }

  /**
   * Get the class of items in this collection, for validation.
   *
   * @return string
   *   A class name.
   */
  abstract public function itemClass() : string;

  /**
   * Throw an Exception if an item is not in the required class.
   *
   * @param mixed $item
   *   The item.
   *
   * @throws \Exception
   */
  public function validate($item) {
    $class = $this->itemClass();
    if (!($item instanceof $class)) {
      throw new \Exception('Item is not of type ' . $class);
    }
  }

  /**
   * Validate all members of the collection.
   *
   * @throws \Exception
   */
  public function validateMembers() {
    foreach ($this->items as $item) {
      $this->validate($item);
    }
  }

}
