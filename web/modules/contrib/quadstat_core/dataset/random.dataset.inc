<?php

/**
 * Helper function to create a random dataset
 */
function _quadstat_core_process_random_dataset($entity) {
  $user = \Drupal::currentUser();
  $rows = $entity->get('field_dataset_random_rows')->value;
  $columns = $entity->get('field_dataset_random_columns')->value;
  $data = '';
  if($entity->get('field_dataset_cont_table')->value == 0) {
    // Populate
    for($i = 0; $i < $rows; $i++) {
      for($j = 0; $j < $columns; $j++) {
        $data .= rand(0, 100) . ",";
      }
      // trim trailing comma
      $data = rtrim($data, ',') . "\n";
    }
    // Add header column to beginning
    $header = '';
    for($i = 1; $i <= $columns; $i++) {
      $header .= '"' . $i . '",';
    }
    $data = rtrim($header, ',') . "\n" . $data;
  } else if($entity->get('field_dataset_cont_table')->value == 1) {
    // Populate
    for($i = 0; $i < 2; $i++) {
      $data .= "R" . $i . ',';
      for($j = 0; $j < 3; $j++) {
        $data .= rand(0, 100) . ",";
      }
      // trim trailing comma
      $data = rtrim($data, ',') . "\n";
    }
    // Add header column to beginning
    $header = '"C1","C2","C3"' . "\n";
    $data = $header . $data;
  }
  
  // Save the newly created CSV dataset 
  $datafile = _quadstat_core_save_file($data, $user);

  // Delete the file if user attached one
  $file = $entity->get('field_dataset_file')->referencedEntities();
  if($file != NULL) {
    $fid = $file[0]->get('fid')->get(0)->value;
    file_delete($fid);
  }

  // Attach the new file to dataset node
  if($datafile) {
    $entity->field_dataset_file->setValue(['target_id' => $datafile->get('fid')->get(0)->value]);
  }
}
