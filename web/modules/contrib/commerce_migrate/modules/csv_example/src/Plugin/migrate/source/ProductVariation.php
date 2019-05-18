<?php

namespace Drupal\commerce_migrate_csv_example\Plugin\migrate\source;

use Drupal\migrate\Row;
use Drupal\migrate_source_csv\Plugin\migrate\source\CSV;

/**
 * Gets the product variations rows.
 *
 * Trims each cell in the each row of the source CSV.
 *
 * @MigrateSource(
 *   id = "csv_example_product_variation"
 * )
 */
class ProductVariation extends CSV {

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    // Trim all the source values.
    foreach ($row->getSource() as $key => $value) {
      if (is_string($value)) {
        $row->setSourceProperty($key, trim($value));
      }
    }
    return parent::prepareRow($row);
  }

}
