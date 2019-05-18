<?php

/**
 * Parse TXT file
 *
 * @param type $file
 * @param type $delimiter
 * @return type
 */
function data_import_parse_txt_file($filename, $delimiter) {
  ini_set('memory_limit', '1024M');
  set_time_limit(0);

  $data = array();

  $ord = unistr_to_ords($delimiter);

  try {
    $file = @fopen($filename, 'r');
    $row = 0;
    while (!feof($file)) {
      $line = fgets($file);
      $data[$row] = explode(chr($ord[0]), $line);
      $row++;
    }
  } catch (Exception $e) {
    data_import_log(DATA_IMPORT_ERROR, $e->getMessage());
  }

  return $data;
}

/**
 * By Darien Hager, Jan 2007...
 *
 * Change "UTF-8" to whichever encoding you are expecting to use.
 *
 * @param type $str
 * @param type $encoding
 * @return type
 *
 * @see http://php.net/manual/fr/function.ord.php
 */
function unistr_to_ords($str, $encoding = 'UTF-8') {
  // Turns a string of unicode characters into an array of ordinal values,
  // Even if some of those characters are multibyte.
  $str = mb_convert_encoding($str, "UCS-4BE", $encoding);
  $ords = array();

  // Visit each unicode character
  for ($i = 0; $i < mb_strlen($str, "UCS-4BE"); $i++) {
    // Now we have 4 bytes. Find their total
    // numeric value.
    $s2 = mb_substr($str, $i, 1, "UCS-4BE");
    $val = unpack("N", $s2);
    $ords[] = $val[1];
  }
  return($ords);
}
