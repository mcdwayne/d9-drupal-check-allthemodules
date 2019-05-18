<?php

namespace Drupal\migrate_sql_subrow\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SqlBase;

/**
 * Provides a base source plugin for importing multiple items per SQL row.
 */
abstract class SqlSubRowBase extends SqlBase {

  /**
   * @var \Iterator
   */
  private $parentIterator;

  /**
   * {@inheritdoc}
   */
  protected function initializeIterator() {
    $rows = [];
    do {
      $this->fetchNextParentRow();

      // If reached last parent row, return the finished iterator.
      if (!$this->parentIterator->valid()) {
        return new \ArrayIterator([]);
      }
      $main_row = $this->parentIterator->current();

      // Check for matches
      $matches = $this->testMainRow($main_row);
      if ($matches) {
        if (!$rows = $this->generateDependentRows($main_row)) {
          $matches = FALSE;
        }
      }
    } while(!$matches);
    return new \ArrayIterator($rows);
  }

  /**
   * Test the row from the SQL query to check if it has any valid sub-rows.
   *
   * If possible make this a quick test. If there are no good tests without
   * doing the full processing, return TRUE here and if needed return an empty
   * array from generateDependentRows().
   *
   * @param array $main_row
   *   The main row from the SQL query.
   *
   * @return boolean
   *   Whether the row has (valid) sub-rows.
   */
  abstract protected function testMainRow(array $main_row);

  /**
   * Generate the sub rows from the primary SQL fetched row.
   *
   * @param array $main_row
   *   The main row from the SQL query.
   *
   * @return array
   *   An array of the rows. Can be an empty array if no valid rows were found.
   */
  abstract protected function generateDependentRows(array $main_row);

  /**
   * Returns the iterator that will yield the row arrays to be processed.
   *
   * @return \Iterator
   *   The iterator that will yield the row arrays to be processed.
   */
  protected function getParentIterator() {
    if (!isset($this->parentIterator)) {
      $this->parentIterator = parent::initializeIterator();
    }
    return $this->parentIterator;
  }

  /**
   * Position the iterator to the following row.
   */
  protected function fetchNextRow() {
    $this->getIterator()->next();
    // We might be out of data entirely, or just out of data in the current
    // main row.
    if (!$this->getIterator()->valid()) {
      $this->iterator = $this->initializeIterator();
      $this->getIterator()->rewind();
    }
  }

  /**
   * Position the parent iterator to the following row.
   *
   * @see SqlBase::fetchNextRow();
   */
  protected function fetchNextParentRow() {
    $this->getParentIterator()->next();
    // We might be out of data entirely, or just out of data in the current
    // batch. Attempt to fetch the next batch and see.
    if ($this->batchSize > 0 && !$this->getParentIterator()->valid()) {
      $this->fetchNextBatch();
    }
  }

  /**
   * Prepares query for the next set of data from the source database.
   *
   * @see SqlBase::fetchNextBatch();
   */
  protected function fetchNextBatch() {
    $this->batch++;
    unset($this->parentIterator);
    $this->getParentIterator()->rewind();
  }
}
