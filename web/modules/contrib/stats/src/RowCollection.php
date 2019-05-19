<?php
/**
 * @file
 * RowCollection.php for kartslalom
 */

namespace Drupal\stats;

/**
 * Holds a set of stat rows to iterate over.
 *
 * @package Drupal\stats
 */
class RowCollection implements \Iterator, \Countable {

  /**
   * @var \Drupal\stats\Row[]
   */
  protected $rows = [];

  /**
   * RowCollection constructor.
   *
   * @param array $rows
   */
  public function __construct(array $rows = []) {
    $this->rows = $rows;
  }

  /**
   * {@inheritdoc}
   */
  public function current() {
    return current($this->rows);
  }

  /**
   * {@inheritdoc}
   */
  public function next() {
    return next($this->rows);
  }

  /**
   * {@inheritdoc}
   */
  public function key() {
    return key($this->rows);
  }

  /**
   * {@inheritdoc}
   */
  public function valid() {
    return !is_null($this->key());
  }

  /**
   * {@inheritdoc}
   */
  public function rewind() {
    reset($this->rows);
  }

  /**
   * {@inheritdoc}
   */
  public function count() {
    return count($this->rows);
  }

  /**
   * Adds a row to the collection.
   *
   * @param \Drupal\stats\Row $row
   */
  public function addRow(Row $row) {
    // Make sure source of row is frozen when it is added.
    $row->freezeSource();
    $this->rows[] = $row;
  }

  /**
   * Empties the given collection.
   */
  public function empty() {
    $this->rows = [];
  }
}
