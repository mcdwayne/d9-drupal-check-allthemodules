<?php

/**
 * @file
 * Handle common forum access functionality.
 */

/**
 * Check access to view forums and containers.
 */
function forum_access_forum_check_view($account, $tid = NULL) {
  return forum_access_access('view', $tid, $account);
}

/**
 * Modify query according to settings.
 */
function forum_access_modify_check_query($query, $settings) {
  // Modify by operation.
  switch ($settings['op']) {
    case 'view':
      $query->condition('grant_view', 0, '>');
      break;

    case 'update':
      $query->condition('grant_update', 0, '>');
      break;

    case 'delete':
      $query->condition('grant_delete', 0, '>');
      break;

    case 'create':
      $query->condition('grant_create', 0, '>');
      break;
  }
  // Modify by tid.
  if (!empty($settings['tid'])) {
    $query->condition('tid', $settings['tid']);
  }
}

/**
 * Access checking.
 */
function forum_access_access($op, $tid, $account = NULL) {
  module_load_include('inc', 'forum_access', 'includes/forum_access.acl');
  $cache = &drupal_static(__FUNCTION__, []);
  // Load current user if account is empty.
  if (!$account) {
    $account = \Drupal::currentUser();
  }
  if ($account->hasPermission('bypass node access')) {
    return TRUE;
  }
  if ($op == 'delete' && $account->hasPermission('delete any forum content')) {
    return TRUE;
  }
  if ($op == 'update' && $account->hasPermission('edit any forum content')) {
    return TRUE;
  }
  if (!isset($cache[$account->id()][$tid][$op])) {
    // Check if user's roles allow him permissions to operators.
    $query = \Drupal::database()->select('forum_access', 'fa')
      ->fields('fa', ['tid']);
    forum_access_modify_check_query($query, [
      'op' => $op,
      'tid' => $tid,
    ]);
    $query->condition('fa.rid', $account->getRoles(), 'IN');
    $result = $query
      ->execute()
      ->fetchField();
    if ($result) {
      $cache[$account->id()][$tid][$op] = TRUE;
    }
    else {
      // Check our moderators too.
      $acl_moderate = forum_access_is_moderator($account, $tid);
      $cache[$account->id()][$tid][$op] = !empty($acl_moderate);
    }
  }
  return $cache[$account->id()][$tid][$op];
}

/**
 * Get forum tid from entity.
 */
function forum_access_get_tid($entity) {
  if ($entity->hasField('taxonomy_forums') && $taxonomy_forum = $entity->get('taxonomy_forums')->first()) {
    return $taxonomy_forum->getValue()['target_id'];
  }
}

/**
 * Get all grants for specific tid.
 */
function forum_access_get_grants_by_tid($tid) {
  $result = \Drupal::database()->select('forum_access', 'fa')
    ->fields('fa')
    ->condition('tid', $tid)
    ->execute()
    ->fetchAll();
  return $result;
}

/**
 * Check access for entity.
 */
function forum_access_entity_access_by_tid($op, $entity) {
  $tid = forum_access_get_tid($entity);
  return forum_access_access($op, $tid);
}

/**
 * Get forum access settings by roles and grant.
 */
function forum_access_get_settings_by_roles($roles, $grant) {
  $query = \Drupal::database()->select('forum_access', 'a')
    ->fields('a', [
      'tid',
      'rid',
      'grant_view',
      'grant_update',
      'grant_delete',
      'grant_create',
      'priority',
    ])
    ->condition('a.rid', $roles, 'IN');
  // Add condition by grant.
  switch ($grant) {
    case 'view':
      $grant_field = 'grant_view';
      break;

    case 'update':
      $grant_field = 'grant_update';
      break;

    case 'delete':
      $grant_field = 'grant_delete';
      break;

    case 'create':
      $grant_field = 'grant_create';
      break;
  }
  if (!empty($grant_field)) {
    $query->condition($grant_field, 1);
  }
  return $query->execute();
}
