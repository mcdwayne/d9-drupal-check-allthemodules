<?php

namespace Drupal\developer_suite;

use ArrayIterator;
use Countable;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use IteratorAggregate;

/**
 * Class Collection.
 *
 * @package Drupal\developer_suite
 */
class Collection implements CollectionInterface, IteratorAggregate, Countable {

  /**
   * The entity type.
   *
   * @var string
   */
  protected $entityType;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $entityStorage;

  /**
   * The entity query.
   *
   * @var \Drupal\Core\Entity\Query\QueryInterface
   */
  protected $entityQuery;

  /**
   * The collection items.
   *
   * @var array
   */
  protected $items = [];

  /**
   * EntityCollection constructor.
   *
   * @param string $entityType
   *   The entity type.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function __construct($entityType, EntityTypeManagerInterface $entityTypeManager) {
    if ($entityType) {
      $this->entityType = $entityType;
      $this->entityTypeManager = $entityTypeManager;
      $this->entityStorage = $this->entityTypeManager->getStorage($this->entityType);
      $this->entityQuery = $this->entityStorage->getQuery();
    }
  }

  /**
   * Adds an item to the collection.
   *
   * @param mixed $item
   *   The item to add.
   * @param mixed $key
   *   The item key.
   */
  public function add($item, $key = NULL) {
    if ($key) {
      $this->items[$key] = $item;
    }
    else {
      $this->items[] = $item;
    }

  }

  /**
   * Removes an item from the collection.
   *
   * @param mixed $index
   *   The item index.
   */
  public function remove($index) {
    unset($this->items[$index]);
  }

  /**
   * Checks if the collection contains an item.
   *
   * @param mixed $value
   *   The value to check.
   *
   * @return bool
   *   Indicates if the value was found in the collection.
   */
  public function contains($value) {
    return in_array($value, $this->items);
  }

  /**
   * Resets the pointer to the first element and returns it.
   *
   * @return mixed
   *   The first element.
   */
  public function first() {
    return reset($this->items);
  }

  /**
   * Finds an element by value and returns its index.
   *
   * @param mixed $value
   *   The value to search for.
   *
   * @return false|int|string
   *   The index if the value is found, else FALSE.
   */
  public function find($value) {
    return array_search($value, $this->items);
  }

  /**
   * Returns an item by its index.
   *
   * @param mixed $index
   *   The item index.
   *
   * @return mixed
   *   The item.
   */
  public function get($index) {
    return $this->items[$index];
  }

  /**
   * Returns the items of the collection.
   *
   * @return array
   *   The items.
   */
  public function getItems() {
    return $this->items;
  }

  /**
   * Returns a count of the items.
   *
   * @return int
   *   The item count.
   */
  public function count() {
    return count($this->items);
  }

  /**
   * Clears the currently loaded items.
   */
  public function clear() {
    return $this->items = [];
  }

  /**
   * Returns an ArrayIterator object with the collections items.
   *
   * @return \ArrayIterator
   *   The ArrayIterator.
   */
  public function getIterator() {
    return new ArrayIterator($this->items);
  }

  /**
   * Loads the collection with the entities.
   *
   * @param mixed $ids
   *   The entity IDs.
   *
   * @return bool|$this
   *   The collection.
   */
  protected function load($ids) {
    if ($this->entityStorage) {
      $this->clear();
      $this->addMultiple($this->entityStorage->loadMultiple($ids));

      return $this;
    }

    return FALSE;
  }

  /**
   * Adds multiple items to the collection.
   *
   * @param array $items
   *   The items to add.
   */
  public function addMultiple(array $items) {
    foreach ($items as $key => $item) {
      $this->add($item, $key);
    }
  }

}
