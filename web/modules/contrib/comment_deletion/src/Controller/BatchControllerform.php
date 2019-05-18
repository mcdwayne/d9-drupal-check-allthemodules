<?php

/**
 * @file
 * Contains \Drupal\comment_deletion\BatchControllerform.
 */

namespace Drupal\comment_deletion\Controller;

/**
 * Controller for Comment Deletion.
 */
 
class BatchControllerform {

  public static function content() {

    // Give helpful information about how many nodes are being operated on.
  $node_count = comment_delete_node_type_count();
  $node_type_count = count($node_count);  
  drupal_set_message(t('@node_count Nodes found', array('@node_count' => $node_type_count, '@count' => ceil($node_type_count / 5))));
  $operations = array();
  for ($i = 0; $i < $node_type_count; $i++) {
    $operations[] = array('comment_delete_batch_operation',
      array($node_type_count, $node_count[$i]),
    );
  }
  $batch = array(
    'operations' => $operations,
    'finished' => 'comment_delete_batch_finish',
    'file' => drupal_get_path('module', 'comment_deletion') . '/comment_deletion.delete.inc',
  // Message displayed while processing the batch. Available placeholders are:
  // These placeholders are replaced with actual values in _batch_process(),
  // nodes per operation.
  // Defaults to t('Completed @current of @total.').
    'title' => t('Processing batch'),
    'init_message' => t('Batch is starting.'),
    'progress_message' => t('Processed @current out of @total.'),
    'error_message' => t('Batch has encountered an error.'),
  );
  batch_set($batch);
  // Page to return to after complete.
  return batch_process('admin/config/comment_deletion');
  }
}

/**
 * Callback for count of nodes.
 */

function comment_delete_node_type_count() {
  // Gives nid of nodes for which comments needs to be delete.
  //$content_type = node_type_get_types();
  $content_type = \Drupal::config('comment_deletion.settings')->get('comment_delete_types');
  foreach ($content_type as $key => $value) {
      $query = \Drupal::entityQuery('node')
      // Filter by content type.
      ->condition('type', $key)
      // Filter by published.
      ->condition('status', 1)
      // Count.
      ->execute();
      foreach($query as $node_info)
      $result[]= $node_info;
    }
    return $result;
}
