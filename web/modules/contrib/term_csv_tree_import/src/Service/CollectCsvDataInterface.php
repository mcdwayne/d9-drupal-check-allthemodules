<?php

namespace Drupal\term_csv_tree_import\Service;

/**
 * Class CollectCsvData.
 *
 * @package Drupal\term_csv_tree_import\Service
 */
interface CollectCsvDataInterface {
  /**
   * Load data to array and create term.
   *
   * @param string $csv_file_path
   *    Csv filename.
   * @param string $vocabulary_id
   *    Vocabulary where to create term.
   *
   * @return mixed
   *    Number of terms processed.
   */
  public function loadData($csv_file_path, $vocabulary_id);

}
