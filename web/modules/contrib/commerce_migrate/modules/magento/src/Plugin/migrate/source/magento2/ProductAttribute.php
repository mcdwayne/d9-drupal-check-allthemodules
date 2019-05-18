<?php

namespace Drupal\commerce_migrate_magento\Plugin\migrate\source\magento2;

use Drupal\migrate_source_csv\Plugin\migrate\source\CSV;

/**
 * Yields each product attribute.
 *
 * The cell containing the Magento attributes is a comma separated list of all
 * the attributes assigned to this product variation. Each cell contains any
 * number of attribute sets as described in the example below.
 *
 * Consider this example.
 * @code
 * activity="Gym"|"Hiking"|"Trail"|"Urban",erin_recommends="Yes"
 * @endcode
 * In this case, 'activity' is an attribute and 'Gym', 'Hiking',, 'Trail' and
 * 'Urban' are it's attribute options. Also, 'erin_recommends' is an attribute
 * with a 'Yes' option. There may be more options for an attribute in other
 * rows.
 *
 * @MigrateSource(
 *   id = "magento2_product_attribute_csv"
 * )
 */
class ProductAttribute extends CSV {

  /**
   * {@inheritdoc}
   */
  public function initializeIterator() {
    $file = parent::initializeIterator();
    return $this->getYield($file);
  }

  /**
   * Prepare one row per attribute.
   *
   * @param \SplFileObject $file
   *   The source CSV file object.
   *
   * @codingStandardsIgnoreStart
   *
   * @return \Generator
   *   A new row, one for each attribute and option pair.
   *
   * @codingStandardsIgnoreEnd
   */
  public function getYield(\SplFileObject $file) {
    foreach ($file as $row) {
      $new_row = [];
      $attributeSet = explode(',', $row['additional_attributes']);
      foreach ($attributeSet as $set) {
        $new_row['attribute'] = strstr($set, '=', TRUE);
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

    if (in_array($row['attribute'], $unique_rows)) {
      return FALSE;
    }
    array_push($unique_rows, $row['attribute']);
    return TRUE;
  }

}
