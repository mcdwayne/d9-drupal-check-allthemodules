<?php

/**
 * Returns a confirmation form to flush data.
 */
function drealty_import_flush_form($form, &$form_state) {
  $form['header'] = array(
    '#markup' => '<h4>' . t('Perform Flush?') . '</h4>',
  );

  $form['confirm_action'] = array(
    '#type' => 'checkbox',
    '#title' => t('Yes, I want to flush all data in the site (Listings, Offices, and Agents).')
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
function drealty_import_flush_form_submit($form, &$form_state) {
  $query = new EntityFieldQuery();
  $query->entityCondition('entity_type', 'drealty_listing');
  $results = $query->execute();

  foreach ($results['drealty_listing'] as $record) {
    $operations[] = array('drealty_import_flush_item', array('entity_type' => 'drealty_listing', 'entity_id' => $record->id, 'details' => t('Deleting listing entity id @key...', array('@key' => $record->id))));
  }

  if (module_exists('drealty_office')) {
    $query = new EntityFieldQuery();
    $query->entityCondition('entity_type', 'drealty_office');
    $results = $query->execute();

    foreach ($results['drealty_office'] as $record) {
      $operations[] = array('drealty_import_flush_item', array('entity_type' => 'drealty_office', 'entity_id' => $record->id, 'details' => t('Deleting office entity id @key...', array('@key' => $record->id))));
    }
  }

  if (module_exists('drealty_agent')) {
    $query = new EntityFieldQuery();
    $query->entityCondition('entity_type', 'drealty_agent');
    $results = $query->execute();

    foreach ($results['drealty_agent'] as $record) {
      $operations[] = array('drealty_import_flush_item', array('entity_type' => 'drealty_agent', 'entity_id' => $record->id, 'details' => t('Deleting agent entity id @key...', array('@key' => $record->id))));
    }
  }

  $batch = array(
    'operations' => $operations,
    'title' => t('Performing drealty flush.'),
    'init_message' => t('Initializing'),
    'error_message' => t('An error occurred.'),
    'finished' => 'drealty_import_flush_batch_finished',
    'file' => drupal_get_path('module', 'drealty_import') . '/includes/flush.batch.inc',
  );

  batch_set($batch);
  $form_state['redirect'] = 'admin/drealty/drealty-import';
}

function drealty_import_flush_item($entity_type, $entity_id, $message, &$context) {
  entity_delete($entity_type, $entity_id);
  $context['message'] = $message;
  $context['results'][] = $entity_id;
}

function drealty_import_flush_batch_finished($success, $results, $operations) {
  if ($success) {
    $message = t('@count entities flushed.', array('@count' => count($results)));
  }
  else {
    $message = t('Finished with an error.');
  }

  drupal_set_message($message);
}