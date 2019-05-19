<?php

/**
 * @file
 * Contains taxonomy_delete.drush.inc.
 */

/**
 * Implements hook_drush_commands().
 */
function taxonomy_delete_drush_command() {
  $items = [];

  $items['term-delete'] = [
    'description' => 'Delete all terms in a vocabulary',
    'aliases' => ['tdel'],
    'drupal dependencies' => ['taxonomy_delete'],
  ];

  return $items;
}

/**
 * Submit handler for term delete.
 */
function drush_taxonomy_delete_term_delete($vid = NULL) {
  if (!isset($vid)) {
    drush_set_error("Missing argument - Vocabulary");
    return;
  }

  $tree = \Drupal::entityTypeManager()
    ->getStorage('taxonomy_term')
    ->loadTree($vid);
  if ($tree) {
    foreach ($tree as $term) {
      $terms[] = $term->tid;
    }
  }
  else {
    drush_set_error("No such vocabulary exists");
    return;
  }
  if (drush_confirm("Are you sure your want to delete all terms in $vid")) {

    $service = \Drupal::service('taxonomy_delete.term_delete');
    $term_count = $service->deleteTermByVid($vid);

    $message = \Drupal::translation()
      ->formatPlural($term_count, 'One term deleted in ', '@count terms deleted in @vid', [
        '@vid' => $vid,
      ]);
    drush_print($message);
  }
  else {
    drush_user_abort();
  }
}
