<?php

/**
 * Returns a confirmation form to flush data.
 */
function drealty_import_process_queue_form($form, &$form_state) {
  $form['header'] = array(
    '#markup' => '<h4>' . t('Process drealty queue items?') . '</h4>',
  );

  $form['confirm_action'] = array(
    '#type' => 'checkbox',
    '#title' => t('Yes, I want to manually process any standing items in the queue.')
  );

  $form['actions'] = array('#type' => 'actions');

  $form['actions']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Submit'),
    '#weight' => 40,
  );

  return $form;
}

/**
 * Submit handler.
 */
function drealty_import_process_queue_form_submit($form, &$form_state) {
  $import = new DrealtyImport();
  $queue_count = $import->queue->numberOfItems();
  $count = 1;

  if ($queue_count) {
    while ($count < $queue_count) {
      #$data = $item->data;
      $operations[] = array(
        'drealty_import_process_queue_item',
        array('item' => NULL, 'details' => t('Processing item...', array()))
      );

      $count++;
    }

    $batch = array(
      'operations' => $operations,
      'title' => t('Processing queue items.'),
      'init_message' => t('Initializing'),
      'error_message' => t('An error occurred.'),
      'finished' => 'drealty_import_process_queue_batch_finished',
      'file' => drupal_get_path('module', 'drealty_import') . '/includes/process.batch.inc',
    );

    batch_set($batch);
  } else {
    drupal_set_message('No items in the queue to process.');
  }

  $form_state['redirect'] = 'admin/drealty/drealty-import';
}

/**
 * Operation item callback.
 * @param $item
 * @param $message
 * @param $context
 */
function drealty_import_process_queue_item($item, $message, &$context) {
  $import = new DrealtyImport();

  if ($item = $import->queue->claimItem()) {
    try {
      $import->processQueueItem($item->data);
      $context['message'] = 'Processing remote identifier ' . $item->data['remote_id'];
      $context['results'][] = $item->data['remote_id'];
    } catch (Exception $e) {
      $import->queue->releaseItem($item);
      watchdog('drealty_import', 'Error during import of listing @id', array('@id' => $item->data['remote_id']));
    }
  }
}

/**
 * Batch finished callback.
 * @param $success
 * @param $results
 * @param $operations
 */
function drealty_import_process_queue_batch_finished($success, $results, $operations) {
  if ($success) {
    $message = t('@jobs queue jobs processed.', array('@jobs' => count($results)));
  }
  else {
    $message = t('Finished with an error.');
  }

  drupal_set_message($message);
}