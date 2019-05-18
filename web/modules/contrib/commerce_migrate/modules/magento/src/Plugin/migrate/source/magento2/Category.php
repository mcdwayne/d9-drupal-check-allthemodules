<?php

namespace Drupal\commerce_migrate_magento\Plugin\migrate\source\magento2;

use Drupal\migrate_source_csv\Plugin\migrate\source\CSV;

/**
 * Yields each taxonomy vocabulary.
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
 * In this case, 'Default Category' and 'Special Category' are the top level
 * and will be migrated to vocabularies, with machine name 'default_category'
 * and 'special_category' respectively.
 *
 * @MigrateSource(
 *   id = "magento2_category_csv"
 * )
 */
class Category extends CSV {

  /**
   * {@inheritdoc}
   */
  public function initializeIterator() {
    $file = parent::initializeIterator();
    return $this->getYield($file);
  }

  /**
   * Prepare one row per taxonomy vocabulary in the source data.
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
    foreach ($file as $row) {
      $new_row = [];
      $categoryGroup = explode(',', $row['categories']);
      foreach ($categoryGroup as $category) {
        $new_row['vocabulary'] = strstr($category, '/', TRUE);
        if ($this->rowUnique($new_row)) {
          yield($new_row);
        }
      }
    }
  }

  /**
   * Tests if the row is unique.
   *
   * @param array $row
   *   An array of attribute_name and attribute_value for the current row.
   *
   * @return bool
   *   Return TRUE if the row is unique, FALSE if it is not unique.
   */
  protected function rowUnique(array $row) {
    static $unique_rows = [];

    if (in_array($row['vocabulary'], $unique_rows)) {
      return FALSE;
    }
    array_push($unique_rows, $row['vocabulary']);
    return TRUE;
  }

}
