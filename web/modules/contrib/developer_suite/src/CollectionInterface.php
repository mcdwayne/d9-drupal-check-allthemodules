<?php

namespace Drupal\developer_suite;

/**
 * Interface CollectionInterface.
 *
 * @package Drupal\developer_suite
 */
interface CollectionInterface {

  /**
   * Adds an item to the collection.
   *
   * @param mixed $item
   *   The item to add.
   * @param mixed $key
   *   The item key.
   */
  public function add($item, $key = NULL);

  /**
   * Removes an item from the collection.
   *
   * @param mixed $index
   *   The item index.
   */
  public function remove($index);

  /**
   * Checks if the collection contains an item.
   *
   * @param mixed $value
   *   The value to check.
   *
   * @return bool
   *   Indicates if the value was found in the collection.
   */
  public function contains($value);

  /**
   * Resets the pointer to the first element and returns it.
   *
   * @return mixed
   *   The first element.
   */
  public function first();

  /**
   * Finds an element by value and returns its index.
   *
   * @param mixed $value
   *   The value to search for.
   *
   * @return false|int|string
   *   The index if the value is found, else FALSE.
   */
  public function find($value);

  /**
   * Returns an item by its index.
   *
   * @param mixed $index
   *   The item index.
   *
   * @return mixed
   *   The item.
   */
  public function get($index);

  /**
   * Returns the items of the collection.
   *
   * @return array
   *   The items.
   */
  public function getItems();

  /**
   * Returns a count of the items.
   *
   * @return int
   *   The item count.
   */
  public function count();

  /**
   * Returns an ArrayIterator object with the collections items.
   *
   * @return \ArrayIterator
   *   The ArrayIterator.
   */
  public function getIterator();

  /**
   * Adds multiple items to the collection.
   *
   * @param array $items
   *   The items to add.
   */
  public function addMultiple(array $items);

}
