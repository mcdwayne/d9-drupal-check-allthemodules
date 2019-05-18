<?php

namespace Drupal\commerce_migrate_woocommerce\Plugin\migrate\source\woo3;

use Drupal\migrate_source_csv\Plugin\migrate\source\CSV;

/**
 * Yields each taxonomy term and vocabulary pair.
 *
 * The cell containing the WooCommerce 3 Tags is a comma separated list of the
 * tags assigned to the product variation in this row.
 *
 * Example:
 * @code
 * Fleece, Organic cotton
 * @endcode
 * In this case, the terms 'Fleece' and 'Organic Cotton' will be added to the
 * Tags vocabulary.
 *
 * @MigrateSource(
 *   id = "woo3_tag_term_csv"
 * )
 */
class TagTerm extends CSV {

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
    foreach ($file as $row) {
      $new_row = $row;
      $tags = explode(',', $row['Tags']);
      foreach ($tags as $tag) {
        $new_row['name'] = trim($tag);
        yield($new_row);
      }
    }
  }

}
