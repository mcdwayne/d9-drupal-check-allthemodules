<?php

use \Drupal\node\Entity;

/**
 * Add an empty row to the bottom
 */
function _quadstat_core_addrow(&$fileArr) {
  $fileArr[count($fileArr)] = '';
}

/**
 * Rename the nth column
 */
function _quadstat_core_rename_column(&$fileArr, $nth, $value) {
  $header = $fileArr[0];
  $headerArr = explode(',', $header);
  array_splice($headerArr, $nth - 1, 1, '"' . $value . '"');
  $fileArr[0] = implode(',', $headerArr); 
}

/**
 * Add empty placeholder to end of rows
 */
function _quadstat_core_addcol(&$fileArr, &$longest_row) {
  $i = 0;
  while($i < count($fileArr) - 1) {
    // Add column to end of every row in file array, pad with commas if necessary
    $tmp = explode(',', $fileArr[$i]);
    while(count($tmp) < $longest_row + 1) {
      $tmp[count($tmp)] = '';
    }
    $fileArr[$i] = implode(',', $tmp);
    $i++;
  }
  $longest_row++;
}

/*
 * Update specific cell value
 */
function _quadstat_core_update_cell(&$fileArr, $row, $cell, $value) {
  $line = $fileArr[$row];
  // Create an array out of the row
  $line = explode(',', $line);
  $len = $cell - count($line);
  if($len > 0) {
    // updated cell is to the right of the last value - pad with commas
    $line = implode(',', $line);
    $line = rtrim($line);
    while($len > 0) {
      $line .= ',';
      $len--;
    }
    $line .= $value;
    $fileArr[$row] = $line;
  }
  else if($len == 0) {
    // updated cell is at the end - just add the value to the end of the line
    array_pop($line);
    $line = rtrim(implode(',', $line), ',');
    if(count($line) > 0) {
      $line .= ',' . $value;
    } else {
      $line = $value;
    }
    $fileArr[$row] = $line;
  }
  else if($len < 0) {
    // update cell is to the left of the last value - replace the value
    $line[(Int) $cell - 1] = str_replace("", "\n", $value);
    $fileArr[$row] = implode(',', $line);
  }
}

function _quadstat_core_editor_process_history(&$fileArr, $history, $is_cont_table) {
  // $history is a textarea, process each CSV line individually
  $histArr = explode("\n", $history);
  foreach($histArr as $diffstr) {
    $arr = explode(',', $diffstr);
    // the first line tells what kind of operation we are doing
    $op = $arr[0];
    if($op == 'addrow') {
      _quadstat_core_addrow($fileArr);
    }
    if($op == 'addcol') {
      if(!isset($longest_row)) {
        $longest_row = $tmp = $arr[1];
      }
      if ($tmp < $longest_row) { 
        _quadstat_core_addcol($fileArr, $longest_row = $longest_row++);
      } else {
        _quadstat_core_addcol($fileArr, $longest_row);
      } 
    }
    if($op == 'update') {
      if ($is_cont_table) {
        $arr[2]++;
      }
      _quadstat_core_update_cell($fileArr, $arr[1], $arr[2], $arr[3]);
    }
    if($op == 'rename') {
      _quadstat_core_rename_column($fileArr, $arr[1], $arr[2]);
    }
  }
}

/**
 * Start processing history if dataset values have been updated
 */
function _quadstat_core_update_dataset(&$entity) {
  if($entity->get('field_dataset_history')->value != '') {
    // Get the file to update
    $file = $entity->get('field_dataset_file')->referencedEntities();
    $uri = $file[0]->get('uri')->get(0)->value;
    $filepath = \Drupal::service('file_system')->realpath($uri);
    $data = file_get_contents($filepath);
    $fileArr = explode("\n", $data);
    // process the history textarea
    _quadstat_core_editor_process_history($fileArr, str_replace("\r", '', $entity->get('field_dataset_history')->value), $entity->get('field_dataset_cont_table')->value);
    file_put_contents($filepath, implode("\n", $fileArr));
    // the changes have been saved, clear the history
    $entity->set('field_dataset_history', '');
  }
}
