<?php

/**
 * Returns a confirmation form to import data for a connection.
 */
function drealty_import_openhouse_import_form($form, &$form_state) {
  $form['header'] = array(
    '#markup' => t('Perform OpenHouse Import?'),
  );

  $options = array();
  $connections = entity_load('drealty_connection_entity');

  foreach ($connections as $key => $connection) {
    $options[$connection->conid] = $connection->name;
  }

  $form['connection'] = array(
    '#type' => 'select',
    '#description' => 'Select a connection to process data for.',
    '#title' => 'Connection',
    '#options' => $options,
    '#required' => TRUE,
  );

  $form['confirm_action'] = array(
    '#type' => 'checkbox',
    '#title' => t('Yes, I want to do an import of all OpenHouse data for this resource.')
  );

  $form['actions'] = array('#type' => 'actions');

  $form['actions']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Import'),
    '#weight' => 40,
  );

  return $form;
}

/**
 * Submit handler.
 */
function drealty_import_openhouse_import_form_submit($form, &$form_state) {
  $import = new DrealtyImport();
  $import->clearQueue();

  $metadata = new drealtyMetaData();
  $connection = entity_load('drealty_connection_entity', array($form_state['values']['connection']));
  $connection = end($connection);
  $mappings = $connection->ResourceMappings();

  // perform queries
  foreach ($mappings as $mapping) {
    $resource = $metadata->FetchResource($mapping->rid);
    $classes = $connection->FetchClasses($resource);
    foreach ($classes as $key => $class) {
      if ($class->systemname != 'OpenHouse') {
        continue;
      }

      if ($class->enabled) {
        $items = $import->ProcessRetsClass($connection, $resource, $class, 'drealty_openhouse');

        foreach ($items as $item) {
          $operations[] = array(
            "drealty_import_openhouse_import_populate_queue",
            array(
              'item' => $item,
              'details' => t('Populating queue for @class id @key...', array('@class' => $item['class']->systemname, '@key' => $item['remote_id']))
            )
          );
        }
      }
    }
  }

  $batch = array(
    'operations' => $operations,
    'title' => t('Creating queue jobs'),
    'init_message' => t('Initializing'),
    'error_message' => t('An error occurred.'),
    'finished' => 'drealty_import_openhouse_import_batch_finished',
    'file' => drupal_get_path('module', 'drealty_import') . '/includes/openhouse.batch.inc',
  );

  batch_set($batch);
  $form_state['redirect'] = 'admin/drealty/drealty-import';
}

/**
 * @param $item
 * @param $message
 * @param $context
 */
function drealty_import_openhouse_import_populate_queue($item, $message, &$context) {
  $queue = DrealtyImport::getQueue();
  $queue->createItem($item);
  $context['message'] = $message;
  $context['results'][] = $item['remote_id'];
}

/**
 * @param $success
 * @param $results
 * @param $operations
 */
function drealty_import_openhouse_import_batch_finished($success, $results, $operations) {
  if ($success) {
    $message = t('@jobs queue jobs created.', array('@jobs' => count($results)));
  }
  else {
    $message = t('Finished with an error.');
  }

  drupal_set_message($message);
}