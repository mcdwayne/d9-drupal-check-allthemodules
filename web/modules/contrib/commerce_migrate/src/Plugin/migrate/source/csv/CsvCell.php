<?php

namespace Drupal\commerce_migrate\Plugin\migrate\source\csv;

use Drupal\migrate_source_csv\Plugin\migrate\source\CSV;

/**
 * Yields source values from a cell that is a comma separated list.
 *
 * Use when the migration needs only one value from the source CSV and that
 * value is itself a comma separated list. The source keys should contain one
 * key only and it is also used as the key in the output row.
 *
 * @MigrateSource(
 *   id = "commerce_migrate_csvcell"
 * )
 */
class CsvCell extends CSV {

  /**
   * {@inheritdoc}
   */
  public function initializeIterator() {
    $file = parent::initializeIterator();
    return $this->getYield($file);
  }

  /**
   * Prepare one row per taxonomy term field in the source.
   *
   * @param \SplFileObject $file
   *   The source CSV file object.
   *
   * @codingStandardsIgnoreStart
   *
   * @return \Generator
   *   A new row with one taxonomy term.
   *
   * @codingStandardsIgnoreEnd
   */
  public function getYield(\SplFileObject $file) {
    $key = reset($this->configuration['keys']);
    foreach ($file as $row) {
      $new_row = [];
      $tags = explode(',', $row['Tags']);
      foreach ($tags as $tag) {
        $new_row[$key] = trim($tag);
        yield($new_row);
      }
    }
  }

}
