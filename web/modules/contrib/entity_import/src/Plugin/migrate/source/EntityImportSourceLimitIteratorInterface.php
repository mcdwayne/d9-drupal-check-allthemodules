<?php

namespace Drupal\entity_import\Plugin\migrate\source;

/**
 * Define entity import source limit iterator interface.
 */
interface EntityImportSourceLimitIteratorInterface {

  /**
   * An iterator that needs to be limited.
   *
   * @return \Iterator
   */
  public function limitedIterator();

  /**
   * Set the iterator limit count.
   *
   * @param int $limit
   *   The limit count to restrict the iterator.
   *
   * @return $this
   */
  public function setLimitCount($limit);

  /**
   * Set the iterator limit offset.
   *
   * @param int $offset
   *   The limit offset for the iterator.
   *
   * @return $this
   */
  public function setLimitOffset($offset);

  /**
   * Reset the base iterator.
   *
   * @return $this
   *   The entity import csv source plugin.
   */
  public function resetBaseIterator();

  /**
   * Get the iterator max count.
   *
   * @return int
   *   The limit iterator count.
   */
  public function getLimitIteratorCount();
}
