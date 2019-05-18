<?php

namespace Drupal\commerce_migrate_csv_example\Plugin\migrate\source;

use Drupal\migrate_source_csv\Plugin\migrate\source\CSV;

/**
 * Yields each attribute name and value pair.
 *
 * There are four attribute name/attribute value pairs in the example source
 * row. Create a new row for each non empty attribute name/value pair. See
 * import_attribute for the column names.
 *
 * @MigrateSource(
 *   id = "csv_example_attribute"
 * )
 */
class Attribute extends CSV {

  /**
   * {@inheritdoc}
   */
  public function initializeIterator() {
    $file = parent::initializeIterator();
    return $this->getYield($file);
  }

  /**
   * Prepares one row per attribute pair in the source row.
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
      $new_row = [];
      for ($i = 1; $i < 5; $i++) {
        $new_row['attribute_name'] = trim($row["attribute_name$i"]);
        $new_row['attribute_value'] = trim($row["attribute_value$i"]);
        if ((!empty($new_row['attribute_name'])) && (!empty($new_row['attribute_value']))) {
          if ($this->rowUnique($new_row)) {
            yield($new_row);
          }
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

    foreach ($unique_rows as $unique) {
      if (($unique['attribute_name'] === $row['attribute_name']) && ($unique['attribute_value'] === $row['attribute_value'])) {
        return FALSE;
      }
    }
    $unique_rows[] = $row;
    return TRUE;
  }

}
