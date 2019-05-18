<?php

namespace Drupal\commerce_migrate_magento\Plugin\migrate\source\m2;

use Drupal\migrate_source_csv\Plugin\migrate\source\CSV;

/**
 * Gets the product rows.
 *
 * The standard CSV export does not include any link between a configurable
 * product and the associated simple products. The 'bundle' type and the bundle
 * products are migrated as individual products.
 *
 * Assumptions:
 * - The product SKUs of a configurable product and the products have the same
 * pattern or root. The root is used to search for the associated products.
 *   - WH09-S-Purple: The root is 'WH09'
 *   - A configurable product has at least one simple product.
 *
 * @MigrateSource(
 *   id = "product_csv"
 * )
 */
class Product extends CSV {

  protected $productData = [];

  protected $fileData = [];

  /**
   * {@inheritdoc}
   */
  public function initializeIterator() {
    $file = parent::initializeIterator();
    return $this->getYield($file);
  }

  /**
   * Prepare one row of product data.
   *
   * @param \SplFileObject $file
   *   The source CSV file object.
   *
   * @codingStandardsIgnoreStart
   *
   * @return \Generator
   *   A new row, one for each unique vocabulary.
   *
   * @codingStandardsIgnoreEnd
   */
  public function getYield(\SplFileObject $file) {
    if (empty($this->productData)) {
      $this->fileData = $this->getFileData($file);
    }
    foreach ($file as $row) {
      $variations = $this->getVariations($row);
      $count = count($variations);
      // Yield a row if this product has one variation or if it configurable and
      // has more than product variation.
      if (($count === 1) || (($count > 1) && ($row['product_type'] === 'configurable'))) {
        // Get the variations and write.
        $new_row = $row;
        $new_row['variations'] = $variations;
        yield($new_row);
      }
    }
  }

  /**
   * Returns an array of product SKUs for the variations of this product.
   *
   * If this product is a variation the return array contains the SKUs for all
   * product variations of the product.
   *
   * @param array $row
   *   The current row.
   *
   * @return array
   *   An array of variation SKUs.
   */
  private function getVariations(array $row) {
    static $searched = [];

    $search_sku = strstr($row['sku'], '-', TRUE);
    if (!$search_sku) {
      $search_sku = $row['sku'];
    }
    if (isset($searched[$search_sku])) {
      $variations = $searched[$search_sku];
    }
    else {
      if (ctype_alpha($search_sku[0])) {
        $pattern = "/" . $search_sku . ".*/";
        $subject = $this->fileData['all_sku'];
        $variations = preg_grep($pattern, $subject);
        // Exclude the search pattern, it is likely the 'configurable' product
        // SKU.
        $variations = array_diff($variations, [$search_sku]);
        $searched[$search_sku] = $variations;
      }
      else {
        $variations = [$row['sku']];
      }
    }
    return $variations;
  }

  /**
   * Prepares an array of product SKU information.
   *
   * @param \SplFileObject $file
   *   The file object for the CSV file being processed.
   *
   * @return array
   *   An array of product SKUs with two keys, 'configurable' and 'all'.
   *   Configurable is an array of all the SKUs for product of type
   *   'configurable' * and 'all' is a list of all SKUs.
   */
  private function getFileData(\SplFileObject $file) {
    $file_data = [];
    if (!$this->fileData) {
      $file_data = [];
      foreach ($file as $row) {
        if ($row['product_type'] === 'configurable') {
          $file_data['configurable'][] = $row['sku'];
        }
        $file_data['all_sku'][] = $row['sku'];
      }
    }
    $file->rewind();
    return $file_data;
  }

}
