<?php

namespace Drupal\cached_computed_field;

/**
 * Default implementation of a collection of expired items.
 */
class ExpiredItemCollection implements ExpiredItemCollectionInterface {

  /**
   * The expired items.
   *
   * @var \Drupal\cached_computed_field\ExpiredItemInterface[]
   */
  protected $items;

  /**
   * Constructs a new ExpiredItemCollection object.
   *
   * @param \Drupal\cached_computed_field\ExpiredItemInterface[] $items
   *   An array of expired items.
   */
  public function __construct(array $items) {
    $this->items = $items;
    $this->validate();
  }

  /**
   * {@inheritdoc}
   */
  public function getItems() {
    return $this->items;
  }

  /**
   * {@inheritdoc}
   */
  public function validate() {
    // Check that every item is an ExpiredItem.
    foreach ($this->items as $item) {
      if (!$item instanceof ExpiredItemInterface) {
        throw new \LogicException('Item does not implement ExpiredItemInterface.');
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getIterator() {
    return new \ArrayIterator($this->items);
  }

  /**
   * {@inheritdoc}
   */
  public function count() {
    return count($this->items);
  }

}
