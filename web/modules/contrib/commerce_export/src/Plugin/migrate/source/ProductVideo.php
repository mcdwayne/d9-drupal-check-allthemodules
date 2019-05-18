<?php

namespace Drupal\commerce_export\Plugin\migrate\source;

use Drupal\migrate_source_csv\Plugin\migrate\source\CSV;

/**
 * Yields values for product video paragraph.
 *
 * There are three for product videos in the example source. Currently, just
 * one is retrieved and sent to the process pipeline. See import_product_video.
 *
 * @MigrateSource(
 *   id = "product_video_csv"
 * )
 */
class ProductVideo extends CSV {

  /**
   * {@inheritdoc}
   */
  public function initializeIterator() {
    $file = parent::initializeIterator();
    return $this->getYield($file);
  }

  /**
   * Prepare one row per product video paragraph entity in the source row.
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
      if ((!empty($row['video1'])) && (!empty($row['thumbnail1']))) {
        yield($row);
      }
    }
  }

}
