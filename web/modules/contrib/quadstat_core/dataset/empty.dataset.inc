<?php

/**
 * Helper function to create an empty dataset
 */
function _quadstat_core_process_empty_dataset($entity) {
  $user = \Drupal::currentUser();

  // Populate
  $data = '';
  for($i = 0; $i < 1; $i++) {
    for($j = 0; $j < 1; $j++) {
      $data .= ",";
    }
    // Row number and data row
    $data = rtrim($data, ',') . "\n";
  }
  // Add header column to beginning
  $header = '';
  for($i = 1; $i <= 1; $i++) {
    $header .= '"' . $i . '",';
  }
  $data = rtrim($header, ',') . "\n" . $data;
  
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
