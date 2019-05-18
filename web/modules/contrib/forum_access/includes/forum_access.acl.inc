<?php

/**
 * @file
 * Integration with ACL.
 */

use Drupal\Core\Database\Database;

/**
 * Returns the ACL ID of the forum.
 */
function forum_access_get_acl($tid, $name) {
  $acl_id = acl_get_id_by_name('forum_access', $name, $tid);
  // Create acl if doesn't exists.
  if (!$acl_id) {
    $acl_id = acl_create_acl('forum_access', $name, $tid);
    // Update all nodes which are related to this term with term's ACL.
    $connection = Database::getConnection();
    $subselect = $connection->select('taxonomy_index', 'n');
    $subselect
      ->fields('n', ['nid'])
      ->condition('n.tid', $tid);
    acl_add_nodes($subselect, $acl_id, 1, 1, 1);
  }
  return $acl_id;
}

/**
 * Get list of settings by user.
 */
function forum_access_get_settings_by_user($module, $uid, $name = NULL, $figure = NULL) {
  $query = Database::getConnection()->select('acl', 'a');
  $query->join('acl_user', 'au', 'a.acl_id = au.acl_id');
  $query
    ->fields('a', ['acl_id', 'module', 'name', 'figure'])
    ->condition('a.module', $module)
    ->condition('au.uid', $uid);
  if (isset($name)) {
    $query->condition('a.name', $name);
  }
  if (isset($figure)) {
    $query->condition('a.figure', $figure);
  }
  return $query->execute()->fetchAll();
}

/**
 * Check if user is moderator of specific forum.
 */
function forum_access_is_moderator($account, $tid) {
  $acl_id = acl_get_ids_by_user('forum_access', $account->id(), 'moderate', $tid);
  return !empty($acl_id);
}
