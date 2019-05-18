<?php

/**
 * @file
 * Contains \Drupal\disc\Controller\DiscBatchController.
 */

namespace Drupal\data_import\Controller;

class DataImportBatchController {

  public static function content($importer_id, $progressive = true) {
    $import_process = &drupal_static('data_import_process');
    $process_id = $import_process['process_id'];

    $importer_settings = data_importer_load($importer_id);
    // Calling all modules implementing hook_data_import_preprocess($process_id, $importer_settings) :
    \Drupal::moduleHandler()->invokeAll('data_import_preprocess', array($process_id, $importer_settings));
    
    $return = getData($importer_id);
    
    $get_datas = $return['datas'];
    $batch = array(
      'title' => 'Import process',
      'operations' => array(
        array('data_import_batch_process_progress', array($return['datas'], $process_id, $return['datas']['importer_id'])),
      ),
      'init_message' => t('Preparing to import ...'),
      'progress_message' => t('Progressing import ...'),
      'finished' => 'data_import_batch_process_finished',
      'file' => drupal_get_path('module', 'data_import') . '/inc/data_import.batch.inc',
    );
    batch_set($batch);
    $batch = &batch_get();
  
    $batch['progressive'] = $progressive;
  
    $url = '';
    if($progressive) $url = 'admin/config/content/data-import';
    return batch_process($url);

  }
}

function getData($importer_id) {
  if (is_null($importer_id))
  return;

  $file = '';
  $data_importer = data_importer_load($importer_id);
  
  // Calling all modules implementing hook_data_import_importer_alter(&$data_importer) :
  \Drupal::moduleHandler()->alter('data_import_importer', $data_importer);
  
  // Init Data import process_id
  $import_process = &drupal_static('data_import_process');
  $import_process['process_id'] = db_next_id();
  $import_process['importer_id'] = $importer_id;

  // Log Starting process
  data_import_log(DATA_IMPORT_INFO, t('Start `@import_id` import process.', array('@import_id' => $importer_id)));

  // If FTP/SFTP, get file
  if (stripos($data_importer['importer_type'], 'ftp') !== FALSE) {
    $secured = 0;
    if (stripos($data_importer['ftp_type'], 'sftp') !== FALSE) {
      $secured = 1;
    }
    $file = data_import_get_file($data_importer, $secured);
  } elseif (!empty($data_importer['upload_file'])) {
    // Uploaded file via BO
    $file = drupal_realpath(\Drupal\file\Entity\File::load($data_importer['upload_file'])->getFileUri());
  }

  if (file_exists($file)) {
    // Parse file
    $data = data_import_parse_file($file, $data_importer);

    // skip line
    if(trim($data_importer['skip_line']) != ""){
      $skip_line = trim($data_importer['skip_line']);
      $skip_line = explode(',',$skip_line);
      foreach($skip_line as $line){
        if(trim($line) != ""){
          unset($data[trim($line)]);
        }
      }
    }

    // Prepare data
    data_import_log(DATA_IMPORT_INFO, t('Prepare data `@import_id`.', array('@import_id' => $importer_id)));

    // Get previous data before insert used for optimizer
    $previous_data = data_import_get_data($importer_id);

    // Insert into table
    data_import_log(DATA_IMPORT_INFO, t('Insert data `@import_id` into data_import_data table.', array('@import_id' => $importer_id)));
    data_import_insert_data($data, $importer_id);
    $progressive = true;
    // Create or update content
    $datas = ['previous_data' => $previous_data, 'importer_id' => $importer_id];
    return data_import_process_import($datas, $progressive);
  } else {

    data_import_log(DATA_IMPORT_ERROR, t('File `@file` doesn\'t exist.', array('@file' => $file)));

  }
}
