<?php

namespace Drupal\wwaf_import;

use Drupal\file\Entity\File;
use Drupal\file\FileInterface;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

/**
 * Defines an Excel file object.
 *
 * @package Drupal\pissei_import.
 */
class ExcelFileObject implements \Countable {

  /**
   * Contains the PHPExcel object.
   *
   * @var \PhpOffice\PhpSpreadsheet\Spreadsheet
   */
  protected $xlsData;

  /**
   * Contains the PHPExcel_Worksheet_RowIterator object.
   *
   * @var \PhpOffice\PhpSpreadsheet\Worksheet\RowIterator
   */
  protected $row;

  /**
   * Construct a new Excel file object.
   *
   * @param string $file_path
   *   Path to the excel file.
   *
   * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
   * @throws \PhpOffice\PhpSpreadsheet\Exception
   */
  public function __construct($file_path = NULL) {
    if ($file_path) {
      $reader = IOFactory::createReaderForFile($file_path);
      $this->xlsData = $reader->load($file_path);
    }
    else {
      $this->xlsData = new Spreadsheet();
    }
    $this->row = $this->xlsData->getActiveSheet()->getRowIterator();
  }

  /**
   * Rewind the iterator to the starting row.
   * @throws \PhpOffice\PhpSpreadsheet\Exception
   */
  public function rewind() {
    $this->row->seek(2);
  }

  /**
   * Retrieve current row data of file.
   *
   * @return array
   *   The current row's data.
   */
  public function current() {
    $i = 0;
    $current_row = [];
    $column_names = $this->getColumnNames();

    $cells = $this->row->current()->getCellIterator();
    $cells->setIterateOnlyExistingCells(FALSE);
    foreach ($cells as $cell) {
      $v = $cell->getValue();
      if (!isset($current_row[$column_names[$i]]) || (isset($current_row[$column_names[$i]]) && $v && is_null($current_row[$column_names[$i]]) )) {
        $current_row[$column_names[$i]] = $v;
      }
      $i++;
    }
    $current_row = array_intersect_key($current_row, array_flip(array_filter(array_keys($current_row), 'is_string')));


    return $current_row;
  }

  /**
   * Set the iterator to its next value.
   */
  public function next() {
    $this->row->next();
  }

  /**
   * Return a count of all available source records.
   *
   * @throws \PhpOffice\PhpSpreadsheet\Exception
   */
  public function count() {
    return $this->xlsData->getActiveSheet()->getHighestDataRow() - 1;
  }

  /**
   * Retrieve an Excel column names.
   *
   * @return array
   *   Get Excel column names.
   *
   * @throws \PhpOffice\PhpSpreadsheet\Exception
   */
  public function getColumnNames() {
    $column_names = [];
    foreach ($this->xlsData->getActiveSheet()->getRowIterator(1, 1) as $row) {
      $i = 0;
      $cells = $row->getCellIterator();
      $cells->setIterateOnlyExistingCells(FALSE);
      foreach ($cells as $cell) {
        $value = $cell->getValue();
        $value = strlen($value) ? $value : $i;
        $column_names[] = $value;
        $i++;
      }
    }

    return $column_names;
  }

  /**
   * Indicate if more rows exist in the worksheet rows that we're iterating.
   *
   * @return bool
   *   TRUE if more rows exist.
   */
  public function valid() {
    return $this->row->valid() && $this->count() >= $this->row->key() - 1 ;
  }

  public function merge($files, $group_field) {
    $spreadsheets = [];
    foreach ($files as $file) {
      if ($file instanceof FileInterface) {
        $file_path = \Drupal::service('file_system')
          ->realpath($file->getFileUri());
      }
      else {
        $file_path = $file;
      }
      $reader = IOFactory::createReaderForFile($file_path);
      $file_data = $reader->load($file_path);
      $spreadsheets[] = $file_data->getActiveSheet()->toArray();
    }
    $sheet =  $this->xlsData->getActiveSheet()->toArray();
    $dst_group_field_index = array_search($group_field, $sheet[0]);
    $src_group_field_indexes = [];
    foreach ($spreadsheets as $spreadsheet) {
      $src_group_field_indexes[] = array_search($group_field, $spreadsheet[0]);
      $sheet[0] = array_merge($sheet[0], $spreadsheet[0]);
    }
    $column_count = count($sheet[0]);
    foreach ($sheet as $index => $row) {
      if ($index == 0) {
        // Skip head
        continue;
      }
      $sheet_group_field_value = $sheet[$index][$dst_group_field_index];
      if (empty($sheet_group_field_value)) {
        continue;
      }
      foreach ($spreadsheets as $sindex => $spreadsheet) {
        foreach ($spreadsheet as $row_index => $col) {
          if ($row_index == 0) {
            // Skip head
            continue;
          }
          $candidate_group_field_value = $spreadsheet[$row_index][$src_group_field_indexes[$sindex]];
          if (empty($sheet_group_field_value)) {
            continue;
          }
          if ($candidate_group_field_value == $sheet_group_field_value) {
            $sheet[$index] = array_merge($sheet[$index], $spreadsheet[$row_index]);
          }
        }
        if (count($sheet[$index]) != $column_count) {
          $start_index = count($sheet[$index]);
          $count = $column_count - $start_index;
          $fill = array_fill($start_index, $count, NULL);
          $sheet[$index] = array_merge($sheet[$index], $fill);
        }
      }
    }
    $this->xlsData->getActiveSheet()->fromArray($sheet);
  }

  public function toArray() {
    return $this->xlsData->getActiveSheet()->toArray();
  }
  public function fromArray($data) {
    $worksheet = $this->xlsData->getActiveSheet()->fromArray($data);
    $this->row = $this->xlsData->getActiveSheet()->getRowIterator();

    return $worksheet;
  }

}
