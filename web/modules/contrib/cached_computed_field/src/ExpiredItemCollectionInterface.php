<?php

namespace Drupal\cached_computed_field;

/**
 * Interface for a class representing a collection of expired items.
 */
interface ExpiredItemCollectionInterface extends \IteratorAggregate, \Countable {

  /**
   * Returns the expired items in the collection.
   *
   * @return \Drupal\cached_computed_field\ExpiredItem[]
   *   An array of expired items.
   */
  public function getItems();

  /**
   * Verifies that all items in the collection are ExpireItemInterface objects.
   *
   * @throws \LogicException
   *   Thrown when one or more of the items are not valid.
   */
  public function validate();

}
