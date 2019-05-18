<?php

/**
 * Process a dataset file uploaded by the user
 */
function _quadstat_core_process_file_dataset(Drupal\Core\Entity\EntityInterface $entity) {

  // Get parameters ready to include in command file
  $header = $entity->get('field_dataset_header')->value != 'TRUE' ? 'FALSE' : 'TRUE';
  $separator = $entity->get('field_dataset_separator')->value;
  $is_cont_table = $entity->get('field_dataset_cont_table')->value;
  $tmp_path = \Drupal::service('file_system')->realpath('temporary://');

  switch ($separator) {
    case 'comma':
      $separator = ',';
    break;

    case 'tab':
      $separator = '\t';
    break;

    case 'space':
      $separator = ' ';
    break;

    case 'whitespace':
      $separator = '';
    break;

    default:
      $separator = ',';
  }

  switch ($is_cont_table) {
    case 0:
      $row_names_read = '';
      $row_names_write = ',row.names = FALSE';
    break;
    
    case 1:
      $row_names_read = ',row.names = 1';
      $row_names_write = ',row.names = TRUE';
    break;
      
  }
  $user = \Drupal::currentUser();
  // Get the identifier for this request
  $identifier = _quadstat_core_get_identifier();

  // get the filepath of the unprocessed upload file
  $file = $entity->get('field_dataset_file')->referencedEntities();
  $uri = $file[0]->get('uri')->get(0)->value;
  $filepath = file_unmanaged_copy($uri, 'temporary://', FILE_EXISTS_REPLACE);
  $filepath = \Drupal::service('file_system')->realpath($filepath);
  // put a newline at end of file so R doesn't throw an error
  file_put_contents($filepath, file_get_contents($filepath) . "\n");
  $csv = "$tmp_path/dataset-$identifier.csv";
  // delete the CSV file if it exists from a previous request
  unlink($csv);

// Get R code ready to save
$commands = <<<RCODE
user_table <- read.table(file = "$filepath", header = $header, sep = "$separator" $row_names_read);
write.table(user_table, file = "$csv", append = FALSE, quote = TRUE, sep = ",", na = "NA", dec = ".", col.names = TRUE $row_names_write);
RCODE;

  // Run the code
  _quadstat_core_execute_r($commands, $identifier);

  // Attach created dataset to node
  file_delete($file[0]->get('fid')->get(0)->value);
  if(is_file($csv)) {
    $dataset = file_get_contents($csv);
    $datafile = _quadstat_core_save_file($dataset, $user, $identifier);
    if($datafile) {
      $entity->field_dataset_file->setValue(['target_id' => $datafile->get('fid')->get(0)->value]);
    } 
  }
  // Let user know of any errors or warnings while processing dataset request
  if(is_file($error_file = "$tmp_path/dataset-$identifier.err") && filesize($error_file) > 0) {
    drupal_set_message(file_get_contents($error_file), 'error');
  }
  if(is_file($warn_file = "$tmp_path/dataset-$identifier.warn") && filesize($warn_file) > 0) {
    drupal_set_message(file_get_contents($warn_file), 'warning');
  }
}
