<?php

/**
 * Batch API callback for data_import functionality
 *
 * @param type $datas
 * @param type $importer_id
 * @param array $context
 */
function data_import_batch_process_progress($datas, $process_id, $importer_id, array &$context) {
  if (empty($context['sandbox'])) {
    $context['sandbox']['progress'] = 0;
    $context['sandbox']['max'] = count($datas['current_data']);
  }

  $item = $datas['current_data'][$context['sandbox']['progress']];

  $import_process = &drupal_static('data_import_process');
  $import_process['process_id'] = $process_id;
  $import_process['importer_id'] = $importer_id;

  // Save item
  data_import_save_item($item, $datas['previous_data'], $importer_id, $context['sandbox']['progress']);

  $context['sandbox']['progress'] ++;

  $arguments = array(
    '@current' => $context['sandbox']['progress'],
    '@total' => $context['sandbox']['max'],
    '@number' => $context['sandbox']['progress'],
  );
  $context['message'] = t('Import @current of @total nodes. Node created @number nodes.', $arguments);

  // Inform the batch engine that we are not finished, and provide an
  // estimation of the completion level we reached.
  if ($context['sandbox']['progress'] != $context['sandbox']['max']) {
    $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
  } else {
    $context['finished'] = 1;
  }

  // Put the total into the results section when we're finished so we can
  // show it to the admin.
  if ($context['finished']) {
    $context['results']['count'] = $context['sandbox']['progress'];
    $context['results']['import_process'] = $import_process;
  }
}

/**
 * Batch API finished callback for data_import functionnality
 *
 * @param type $success
 * @param type $results
 * @param type $operations
 */
function data_import_batch_process_finished($success, $results, $operations) {
  $message = '';
  if (isset($results['count'])) {
    $message .= \Drupal::translation()->formatPlural($results['count'], '1 item processed successfully. ', '@count items successfully processed. ');
  }
  if ($success) {
    $type = 'status';
    $import_process = &drupal_static('data_import_process');
    $import_process = $results['import_process'];
    data_import_log(DATA_IMPORT_INFO, t('The import process is finished', array()));
  } else {
    // An error occurred. $operations contains the unprocessed operations.
    $error_operation = reset($operations);
    $message .= ' ' . t('An error occurred while processing @num with arguments: @args', array('@num' => $error_operation[0], '@args' => print_r($error_operation[0], TRUE)));
    $type = 'error';
  }

  $process_id = $results['import_process']['process_id'];
  $importer_settings = data_importer_load($results['import_process']['importer_id']);
  // Calling all modules implementing hook_data_import_afterprocess($process_id, $importer_settings, $success) :
  \Drupal::moduleHandler()->invokeAll('data_import_afterprocess', array($process_id, $importer_settings, $success));

  drupal_set_message($message, $type);
}
