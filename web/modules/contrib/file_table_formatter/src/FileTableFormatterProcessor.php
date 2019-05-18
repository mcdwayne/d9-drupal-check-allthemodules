<?php

namespace Drupal\file_table_formatter;

use Drupal\Core\File\FileSystem;

class FileTableFormatterProcessor implements FileTableFormatterProcessorInterface {

  /**
   * {@inheritdoc}
   */
  public function getTableDataFromFile(\Drupal\file\Entity\File $file, $includes_header = FALSE) {
    $data = [];

    // Currently only supports CSV files
    if ($file->getMimeType() == 'text/csv') {
      // Read the file
      if (($fhandle = fopen($file->getFileUri(), 'r')) !== FALSE) {
        while ($row = fgetcsv($fhandle)) {
          $data[] = $row;
        }
        fclose($fhandle);
      }
    }
    
    $item = NULL;
    $header = NULL;
    $rows = NULL;
    $title = NULL;
    
    if (!empty($file->_referringItem)) {
      $item = $file->_referringItem;
      $title = !empty($file->_referringItem->description) ? $file->_referringItem->description : NULL;
    }
    if (!empty($data)) {
      // Handle header.
      if ($includes_header) {
        $header = array_shift($data);
      }
      $rows = $data;
    }
    return new FileTableData($file, $item, $rows, $header, $title);
  }

}
