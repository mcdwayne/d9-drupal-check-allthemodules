<?php

/**
 * Callback for batch operation.
 */
function comment_delete_batch_operation($node_count, $node_nid, &$context) {
  // Here we actually perform our 'deletion of comments' on the current node.
  $node = \Drupal::entityManager()->getStorage('node')->load($node_nid);
  $cids = \Drupal::entityQuery('comment')
    ->condition('entity_id', $node->nid->value)
    ->condition('entity_type', 'node')
    ->sort('cid', 'DESC')
    ->execute();
  if ($cids) {
    if (isset($context['results']['comment_count'])) {
      $context['results']['comment_count'] += count($cids);
    }
    else {
      $context['results']['comment_count'] = count($cids);
    }
    entity_delete_multiple('comment', $cids);
  }
}

/**
 * Callback for deletion of multiple comments.
 */

function comment_delete_batch_finish($success, $results, $operations) {
  if ($success) {
    // Display the number of comments deleted...
    drupal_set_message(t('%final Comments deleted', array('%final' => end($results) > 0 ? end($results) : 0)));
  }
  else {
    // An error occurred.
    // $operations contains the operations that remained unprocessed.
    $error_operation = reset($operations);
    drupal_set_message(
      t('An error occurred while processing @operation with arguments : @args',
      array(
        '@operation' => $error_operation[0],
        '@args' => print_r($error_operation[0], TRUE),
      )));
  }
}