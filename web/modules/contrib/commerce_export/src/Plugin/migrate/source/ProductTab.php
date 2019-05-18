<?php

namespace Drupal\commerce_export\Plugin\migrate\source;

use Drupal\migrate_source_csv\Plugin\migrate\source\CSV;

/**
 * Yields values for each product tab paragraph.
 *
 * There are two set of columns for the paragraph product tab in each row of the
 * example source. Create a new row for each set where at least one value of the
 * set is non empty.See import_tab for the column names.
 *
 * @MigrateSource(
 *   id = "product_tab_csv"
 * )
 */
class ProductTab extends CSV {

  /**
   * {@inheritdoc}
   */
  public function initializeIterator() {
    $file = parent::initializeIterator();
    return $this->getYield($file);
  }

  /**
   * Prepares one row per tab paragraph values in the source row.
   *
   * @param \SplFileObject $file
   *   The source CSV file object.
   *
   * @codingStandardsIgnoreStart
   *
   * @return \Generator
   *   A new row, one for each filename in the source image column.
   *
   * @codingStandardsIgnoreEnd
   */
  public function getYield(\SplFileObject $file) {
    foreach ($file as $row_num => $row) {
      for ($i = 1; $i < 3; $i++) {
        if (!empty($row["tab_title$i"]) || !empty($row["tab_content$i"]) || !empty($row["tab_content$i"])) {
          $new_row = $row;
          $new_row['tab_title'] = trim($row["tab_title$i"]);
          $new_row['tab_content'] = trim($row["tab_content$i"]);
          $new_row['tab_cta'] = trim($row["tab_cta$i"]);
          yield($new_row);
        }
      }
    }
  }

}
