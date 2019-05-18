<?php

/**
 * @file
 * Batch-related functions for Google NL Autotag module.
 */

use Drupal\node\Entity\Node;

/**
 * Callback for when the batch operation to autotag content is finished.
 *
 * @param bool $success
 *   A boolean indicating whether the batch has completed successfully.
 * @param array $results
 *   The value set in $context['results'] by callback_batch_operation().
 * @param array $operations
 *   If $success is FALSE, contains the operations that remained unprocessed.
 */
function google_nl_autotag_batch_update_callback($success, array $results, array $operations) {
  if ($success) {
    \Drupal::messenger()->addStatus(t('Successfully saved @total nodes.', [
      '@total' => $results['total'],
    ]));
  }
  else {
    \Drupal::messenger()->addError(t('Failed to save nodes.'));
  }
}

/**
 * Batch operation for resaving content to update Google NL tags.
 *
 * @param array $nids
 *   Node IDs to process.
 * @param array $context
 *   Batch context tracking.
 */
function google_nl_autotag_batch_update_content(array $nids, array &$context) {
  if (empty($context['sandbox'])) {
    $context['sandbox']['max'] = count($nids);
    $context['sandbox']['progress'] = 0;
  }

  $items = array_slice($nids, $context['sandbox']['progress'], 50);

  /** @var \Drupal\node\Entity\Node[] $nodes */
  $nodes = Node::loadMultiple($items);
  foreach ($nodes as $node) {
    $node->save();
  }

  $context['sandbox']['progress'] += count($nodes);
  $context['results']['total'] = $context['sandbox']['progress'];
  $context['message'] = t('Saved @current/@total nodes.', [
    '@current' => $context['sandbox']['progress'],
    '@total' => count($nids),
  ]);

  // Setting percentage done.
  if ($context['sandbox']['progress'] < $context['sandbox']['max']) {
    $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
  }
}
