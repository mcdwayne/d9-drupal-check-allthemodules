<?php

namespace Drupal\file_table_formatter;

interface FileTableFormatterProcessorInterface {

  /**
   * @param \Drupal\file\Entity\File $file
   * @param bool|FALSE $includes_header
   * @return FileTableData
   */
  public function getTableDataFromFile(\Drupal\file\Entity\File $file, $includes_header = FALSE);
  
}
