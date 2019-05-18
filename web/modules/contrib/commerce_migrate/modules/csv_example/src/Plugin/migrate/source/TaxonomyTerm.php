<?php

namespace Drupal\commerce_migrate_csv_example\Plugin\migrate\source;

use Drupal\migrate_source_csv\Plugin\migrate\source\CSV;

/**
 * Yields each taxonomy vocabulary and term pair.
 *
 * There are six columns in the example source for three pairs of taxonomy
 * vocabulary name/taxonomy terms. Create a new row for each pair when both
 * values are non empty. See import_taxonomy for the column names.
 *
 * @MigrateSource(
 *   id = "csv_example_taxonomy_term"
 * )
 */
class TaxonomyTerm extends CSV {

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
   *   A new row, one for each filename in the source image column.
   *
   * @codingStandardsIgnoreEnd
   */
  public function getYield(\SplFileObject $file) {
    foreach ($file as $row) {
      $new_row = [];
      for ($i = 1; $i < 4; $i++) {
        $new_row['vocabulary_name'] = trim($row["vocabulary_name$i"]);
        $new_row['term'] = trim($row["term$i"]);
        if (!empty($new_row['vocabulary_name']) && !empty($new_row['term'])) {
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
      if (($unique['vocabulary_name'] === $row['vocabulary_name']) && ($unique['term'] === $row['term'])) {
        return FALSE;
      }
    }
    $unique_rows[] = $row;
    return TRUE;
  }

}
