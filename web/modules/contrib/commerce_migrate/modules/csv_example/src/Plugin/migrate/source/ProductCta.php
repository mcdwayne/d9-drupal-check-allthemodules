<?php

namespace Drupal\commerce_migrate_csv_example\Plugin\migrate\source;

use Drupal\migrate_source_csv\Plugin\migrate\source\CSV;

/**
 * Yields values for each product CTA paragraph.
 *
 * There are two set of columns for the paragraph product CTA in each row of the
 * example source. Create a new row for each set where at least one value of the
 * set is non empty. See import_cta for the column names.
 *
 * @MigrateSource(
 *   id = "csv_example_product_cta"
 * )
 */
class ProductCta extends CSV {

  /**
   * {@inheritdoc}
   */
  public function initializeIterator() {
    $file = parent::initializeIterator();
    return $this->getYield($file);
  }

  /**
   * Prepare one row per CTA paragraph in the source row.
   *
   * @param \SplFileObject $file
   *   The source CSV file object.
   *
   * @codingStandardsIgnoreStart
   *
   * @return \Generator
   *   A new row, one for each CTA paragraph field.
   *
   * @codingStandardsIgnoreEnd
   */
  public function getYield(\SplFileObject $file) {
    foreach ($file as $row) {
      for ($i = 1; $i < 3; $i++) {
        if (!empty($row["cta_title$i"]) || !empty($row["cta_link$i"]) || !empty($row["cta_image$i"])) {
          $new_row = $row;
          $new_row['cta_title'] = trim($row["cta_title$i"]);
          $new_row['cta_link'] = trim($row["cta_link$i"]);
          $new_row['cta_image'] = trim($row["cta_image$i"]);
          yield($new_row);
        }
      }
    }
  }

}
