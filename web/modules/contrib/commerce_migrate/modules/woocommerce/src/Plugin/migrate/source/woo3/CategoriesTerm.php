<?php

namespace Drupal\commerce_migrate_woocommerce\Plugin\migrate\source\woo3;

use Drupal\migrate_source_csv\Plugin\migrate\source\CSV;

/**
 * Yields each taxonomy term in the Categories column.
 *
 * The cell containing the WooCommerce 3 categories is a comma separated list of
 * the categories assigned to the product variation in this row. Each category
 * is a string of categories with a greater than sign delimiter. The term on the
 * left of the > is the parent of the term on the right side. The depth of the
 * hierarchy is increased by adding '> child' strings, as many as is needed.
 *
 * Example:
 * @code
 * Clothing > Hoodies, Clothing > Hoodies > Pocket, Clothing > Hoodies > Zip
 * @endcode
 * In this case, the term 'Clothing' is the parent of 'Hoodies' which is the
 * parent of 'Pocket' and the parent of 'Zip'.
 *
 * @MigrateSource(
 *   id = "woo3_categories_term_csv"
 * )
 */
class CategoriesTerm extends CSV {

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
   *   A new row with a taxonomy term and it's parent.
   *
   * @codingStandardsIgnoreEnd
   */
  public function getYield(\SplFileObject $file) {
    foreach ($file as $row) {
      $new_row = $row;
      $categorySet = explode(',', $row['Categories']);
      foreach ($categorySet as $category) {
        $names = explode('>', $category);
        $previous_name = 0;
        // Build a row for each term name.
        foreach ($names as $name) {
          $new_row['parent'] = $previous_name;
          $previous_name = trim($name);
          $new_row['name'] = trim($name);
          yield($new_row);
        }
      }
    }
  }

}
