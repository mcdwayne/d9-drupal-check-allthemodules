<?php

/**
 * Parse XLS file and encode data to JSON
 *
 * @param type $file
 * @param type $delimiter
 * @return type
 */
function data_import_parse_xls_file($file, $delimiter) {
  // call for phpexcel tools
  include(libraries_get_path('PHPExcel')."/Classes/PHPExcel/IOFactory.php" );
  module_load_include('inc', 'phpexcel');

  $data = array();

  try {
    $raw_data = phpexcel_import($file, FALSE);
    for ($i = 0; $i < count($raw_data[0]); $i++) {
      for ($j = 0; $j < count($raw_data[0][$i]); $j++) {
        $data[$i][$j] = $raw_data[0][$i][$j];
      }
    }
  } catch (Exception $e) {
    data_import_log(DATA_IMPORT_ERROR, $e->getMessage());
  }

  return $data;
}
