<?php

/**
 * Parse CSV file and encode data to JSON
 *
 * @param type $file
 * @param type $delimiter
 * @return type
 */
function data_import_parse_csv_file($path,$delimiter) {
  $data = array();

  try {
    $file = @fopen($path, 'r');
    $delimiter = ($delimiter)?$delimiter:',';

    // Get data
    while (!feof($file)) {
      $data[] = fgetcsv($file,0,$delimiter);
    }
    // Remove empty lines
    while ($empty = array_search(null, $data)) {
      unset($data[$empty]);
    }

    fclose($file);

  } catch (Exception $e) {
    data_import_log(DATA_IMPORT_ERROR, $e->getMessage());
  }

  return $data;
}
