<?php
/**
 * @file
 * RowOnly.php for kartslalom
 */

namespace Drupal\stats\Plugin\StatStep;

use Drupal\stats\Row;
use Drupal\stats\RowCollection;

/**
 * Trait for providing a row only implementation for stat step.
 */
trait RowOnlyTrait {

  /**
   * {@inheritdoc}
   */
  public function process(RowCollection $collection) {
    foreach ($collection as $row) {
      $this->processRow($row);
    }
  }

  /**
   * Handles a single row without any relation to the collection.
   *
   * @param \Drupal\stats\Row $row
   *
   * @return mixed
   */
  abstract protected function processRow(Row $row);

}
