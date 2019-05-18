<?php

use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\node\Entity;

/**
 * Helper function to make sure new dataset is attached to node
 */
function _quadstat_core_validate_new_dataset($entity, $user) {
  $file = $entity->get('field_dataset_file')->referencedEntities();
  if(!isset($file[0]) || is_null($file[0])) {
    // delete the node if no dataset was created
    entity_delete_multiple('node', [$entity->id()]);
    drupal_set_message('An error occurred while importing the dataset. Please try again or contact the site administrator if this problem persists.', 'error');
    $response = new RedirectResponse('/node/add/dataset');
    $response->send();
    exit();
  }
}

/**
 * Helper function to generate a random dataset filename
 */
function _quadstat_core_save_file($data, $user, $identifier = NULL) {
  // Save the newly created CSV dataset 
  $dir = 'private://datasets/' . $user->getUsername();
  if($identifier == NULL) {
    $filename = '/dataset-' . _quadstat_core_get_identifier() . '.csv';
  } else {
    $filename = '/dataset-' . $identifier . '.csv';
  }
  if(file_prepare_directory($dir, FILE_CREATE_DIRECTORY)) {
    while (!$datafile = file_save_data($data, $dir . $filename, FILE_EXISTS_ERROR)) {
      // filename collision, try again
      $filename = '/dataset-' . _quadstat_core_get_identifier() . '.csv';
    }
    return $datafile;
  }
  return FALSE;
}

function _quadstat_core_create_dataset(&$entity) {
  $user = \Drupal::currentUser();
  switch ($entity->get('field_dataset_input_method')->value) {
    case 'random':
      // User wants a dataset of random numbers
      _quadstat_core_process_random_dataset($entity);
    break;

    case 'file':
      // User wants to create a dataset from a file; check the file is attached
      $file = $entity->get('field_dataset_file')->referencedEntities();
      if($file == NULL) {
        drupal_set_message('No file attached. Please upload a file before submitting this form or choose another input method', 'error', TRUE);
        $response = new RedirectResponse('/node/add/dataset');
        $response->send();
        exit();
      }
      // Start processing dataset
      _quadstat_core_process_file_dataset($entity);
    break;

    case 'paste':
      // User wants to create a dataset from pasted textarea
      if($entity->get('field_dataset_paste')->value == '') {
        drupal_set_message('No data copied and pasted. Please copy a dataset to the textarea  or choose another input method', 'error', TRUE);
        $response = new RedirectResponse('/node/add/dataset');
        $response->send();
        exit();
      }
      // Start processing dataset
      _quadstat_core_process_pasted_dataset($entity);
    break;

    // Create an empty dataset (no values)
    case 'empty':
      _quadstat_core_process_empty_dataset($entity);
    break;
  }
}

/**
 * Implements hook_file_download()
 */
function quadstat_core_file_download($uri) {
  $account = \Drupal::currentUser(); 
  $scheme = file_uri_scheme($uri);
  $target = file_uri_target($uri);
  $path_parts = pathinfo($target);
  $ext = $path_parts['extension'];
  if ($scheme == 'private' &&  $ext == 'csv')  {
    $result = db_select('file_managed', 'f')->fields('f')->condition('uri', $uri)->execute()->fetchAssoc();
    $result = db_select('node__field_dataset_file', 'n')->fields('n')->condition('field_dataset_file_target_id', $result['fid'])->execute()->fetchAssoc();
    $node = node_load($result['entity_id']);
    if($node->access('view', $account)) {
      return array(
        "Content-disposition' => 'attachment; filename=\"$target\"",
      );
    } else {
      return -1;
    }
  }
}
