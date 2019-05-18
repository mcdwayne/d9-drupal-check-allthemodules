<?php

namespace Drupal\commerce_migrate_magento\Plugin\migrate\source\magento2;

use Drupal\migrate_source_csv\Plugin\migrate\source\CSV;

/**
 * Yields each taxonomy term and vocabulary pair..
 *
 * The cell containing the Magento categories is a comma separated list of the
 * categories assigned to the product variation in this row. Each category is
 * a string of categories with a forward slash delimiter. The first one is the
 * top level category and it is used as the taxonomy vocabulary. The following
 * categories are terms in that vocabulary listed in hierarchical order.
 *
 * Consider this example.
 * @code
 * Default Category/Gear/Bags,Special Category/Collections/New Yoga Collection
 * @endcode
 *
 * In this case, 'Default Category' and 'Special Category' are the top level
 * and will be migrated to vocabularies, with machine name 'default_category'
 * and 'special_category' respectively.
 *
 * @MigrateSource(
 *   id = "magento2_category_term_csv"
 * )
 */
class CategoryTerm extends CSV {

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
   *   A new row with properties, 'vocabulary', 'parent' and 'term'.
   *
   * @codingStandardsIgnoreEnd
   */
  public function getYield(\SplFileObject $file) {
    foreach ($file as $row) {
      $new_row = $row;
      $categories = explode(',', $row['categories']);
      foreach ($categories as $category) {
        $names = explode('/', $category);
        // Set the vocabulary key to this name.
        $new_row['vocabulary'] = $names[0];
        unset($names[0]);
        $previous_name = '';
        // Build a row for each term name.
        foreach ($names as $name) {
          if ($previous_name) {
            $new_row['parent'] = $previous_name;
          }
          $previous_name = $name;
          $new_row['name'] = $name;
          yield($new_row);
        }
      }
    }
  }

}
