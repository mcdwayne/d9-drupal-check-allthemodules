<?php

namespace Drupal\commerce_migrate_csv_example\Plugin\migrate\source;

use Drupal\migrate\Row;
use Drupal\migrate_source_csv\Plugin\migrate\source\CSV;

/**
 * Builds the product rows.
 *
 * Each row in the source CSV contains one product variation with a unique
 * SKU. And all variations for a product have the same title. Using the title
 * field as the key, create a set  of product rows that has the variation SKUs
 * as an array.
 *
 * @MigrateSource(
 *   id = "csv_example_product"
 * )
 */
class Product extends CSV {

  /**
   * The file object that reads the CSV file.
   *
   * @var \SplFileObject
   */
  protected $file = NULL;

  /**
   * {@inheritdoc}
   */
  public function initializeIterator() {
    $file = parent::initializeIterator();
    // Get the product rows.
    $rows = $this->getProductsWithVariations($file);
    return new \ArrayIterator($rows);
  }

  /**
   * Builds a product row including an array of variation SKUs for this product.
   *
   * @param \SplFileObject $file
   *   The source CSV file object.
   *
   * @return array
   *   The product array.
   */
  protected function getProductsWithVariations(\SplFileObject $file) {
    // Initialize the new row.
    $new_row = [];
    foreach ($file as $line) {
      if (array_key_exists($line['title'], $new_row)) {
        // The new_row has a row for this product, add this SKU.
        $new_row[$line['title']]['variation_sku'][] = trim($line['sku']);
      }
      else {
        // This is a new product, initialize the new_row.
        $line['variation_sku'][] = trim($line['sku']);
        $new_row[$line['title']] = $line;
      }
    }
    return $new_row;
  }

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
