<?php

/**
 * @file
 * Pathauto integration for comments.
 *
 * @ingroup pathauto
 */

/**
 * Implements hook_path_alias_types().
 */
function og_sm_comment_path_alias_types() {
  $objects['comment/'] = t('Comments');
  return $objects;
}

/**
 * Implements hook_pathauto().
 */
function og_sm_comment_pathauto($op) {
  switch ($op) {
    case 'settings':
      $settings = array();
      $settings['module'] = 'comment';
      $settings['token_type'] = 'comment';
      $settings['groupheader'] = t('Comment paths');
      $settings['patterndescr'] = t('Default path pattern');
      $settings['patterndefault'] = 'comment/[comment:cid]';
      $settings['batch_update_callback'] = 'og_sm_comment_pathauto_bulk_update_batch_process';
      $settings['batch_file'] = drupal_get_path('module', 'og_sm_comment') . '/og_sm_comment.pathauto.inc';
      return (object) $settings;

    default:
      break;
  }
}

/**
 * Batch processing callback; Generate aliases for comments.
 */
function og_sm_comment_pathauto_bulk_update_batch_process(&$context) {
  if (!isset($context['sandbox']['current'])) {
    $context['sandbox']['count'] = 0;
    $context['sandbox']['current'] = 0;
  }

  $query = db_select('comment', 'c');
  $query->leftJoin('url_alias', 'ua', "CONCAT('comment/', c.cid) = ua.source");
  $query->addField('c', 'cid');
  $query->isNull('ua.source');
  $query->condition('c.cid', $context['sandbox']['current'], '>');
  $query->orderBy('c.cid');
  $query->addTag('pathauto_bulk_update');
  $query->addMetaData('entity', 'comment');

  // Get the total amount of items to process.
  if (!isset($context['sandbox']['total'])) {
    $context['sandbox']['total'] = $query->countQuery()->execute()->fetchField();

    // If there are no comments to update, the stop immediately.
    if (!$context['sandbox']['total']) {
      $context['finished'] = 1;
      return;
    }
  }

  $query->range(0, 25);
  $comment_ids = $query->execute()->fetchCol();

  og_sm_comment_pathauto_update_alias_multiple($comment_ids, 'bulkupdate');
  $context['sandbox']['count'] += count($comment_ids);
  $context['sandbox']['current'] = max($comment_ids);
  $context['message'] = t('Updated alias for comment @comment_id.', array('@comment_id' => end($comment_ids)));

  if ($context['sandbox']['count'] != $context['sandbox']['total']) {
    $context['finished'] = $context['sandbox']['count'] / $context['sandbox']['total'];
  }
}
