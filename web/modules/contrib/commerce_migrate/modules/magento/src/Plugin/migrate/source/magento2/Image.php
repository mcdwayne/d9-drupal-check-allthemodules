<?php

namespace Drupal\commerce_migrate_magento\Plugin\migrate\source\magento2;

use Drupal\migrate_source_csv\Plugin\migrate\source\CSV;

/**
 * Yields each image and sku.
 *
 * There are several columns for images and image labels in the CSV export
 * produced by Magento. Create a new row for each image.
 *
 * @MigrateSource(
 *   id = "magento2_image_csv"
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
    // The Magento 2 CSV has 4 sets of columns for single images.
    $image_types = [
      ['base_image', 'base_image_label'],
      ['small_image', 'small_image_label'],
      ['thumbnail_image', 'thumbnail_image_label'],
      ['swatch_image', 'swatch_image_label'],
    ];
    foreach ($file as $row) {
      foreach ($image_types as $image_type) {
        $new_row = [];
        // The sku is a key.
        $new_row['sku'] = trim($row['sku']);
        // Base image.
        if (!empty($row[$image_type[0]])) {
          // The image is a key.
          $new_row['image'] = trim($row[$image_type[0]]);
          $new_row['label'] = trim($row[$image_type[1]]);
          yield($new_row);
        }
      }
      // The additional images column may have more that one image per cell.
      $additional_images = explode(',', $row['additional_images']);
      $additional_image_labels = explode(',', $row['additional_image_labels']);
      foreach ($additional_images as $index => $value) {
        $new_row = [];
        $new_row['sku'] = trim($row['sku']);
        // The image is a key.
        $new_row['image'] = trim($value);
        if (isset($additional_image_labels[$index])) {
          $new_row['label'] = trim($additional_image_labels[$index]);
        }
        yield($new_row);
      }
    }
  }

}
