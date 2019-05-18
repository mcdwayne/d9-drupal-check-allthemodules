<?php

namespace Drupal\commerce_migrate_csv_example\Plugin\migrate\source;

use Drupal\migrate_source_csv\Plugin\migrate\source\CSV;

/**
 * Yields each image and sku.
 *
 * There are several columns for image names in the example source. Three are
 * images of the product variation, three are for thumbnails for videos, and
 * two are for the CTA image. Create a new row for image and SKU where both are
 * non empty. See import_image for the column names.
 *
 * @MigrateSource(
 *   id = "csv_example_image"
 * )
 */
class Image extends CSV {

  /**
   * {@inheritdoc}
   */
  public function initializeIterator() {
    $file = parent::initializeIterator();
    return $this->getYield($file);
  }

  /**
   * Prepare one row per image file in the source row.
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
    foreach ($file as $row) {
      if (!empty($row['sku'])) {
        // There is a SKU so let's check for images.
        $new_row = [];
        $new_row['sku'] = trim($row['sku']);
        // Product variation images.
        for ($i = 1; $i < 4; $i++) {
          $new_row['image'] = trim($row["image$i"]);
          if (!empty($new_row['image'])) {
            yield($new_row);
          }
        }
        // Video thumbnails.
        for ($i = 1; $i < 4; $i++) {
          $new_row['image'] = trim($row["thumbnail$i"]);
          if (!empty($new_row['image'])) {
            yield($new_row);
          }
        }
        // Call to action images.
        for ($i = 1; $i < 3; $i++) {
          $new_row['image'] = trim($row["cta_image$i"]);
          if (!empty($new_row['image'])) {
            yield($new_row);
          }
        }
      }
    }
  }

}
