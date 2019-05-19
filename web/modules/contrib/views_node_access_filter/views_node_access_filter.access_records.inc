<?php

/**
 * Drupal's current node access system relies on two sub-systems:
 * - When an individual view is displayed, hook_node_access() is invoked.
 * - When a list of nodes is built, through an SQL query (typically with
 * Views),
 * hook_node_access() is not called, for performance reasons, and the query
 * is just altered to check a node access registry table.
 *
 * The problem is that core only registers a view grant in database, as the
 * update access is never used to retrieve lists.
 * As we need it, we build the grants that match core behavior: update access
 * is given per role and content type, for own or any nodes.
 *
 * If other modules alter the update access through hook_node_access(), they
 * should also implement hook_node_grants() and hook_node_access_records() to
 * be taken into account by this module. But no security worry if they don't,
 * the node might be listed if view access is allowed, but the edit access will
 * still be denied it the other module says so.
 *
 * We do not need to trigger an access registry update on our own as it's
 * already called by core when the node is saved, when permissions are saved or
 * when a module is enabled or disabled.
 **/

/**
 * Implements hook_node_grants().
 *
 * @see node_query_node_access_alter()
 * @see node_node_access()
 */
function views_node_access_filter_node_grants(\Drupal\Core\Session\AccountInterface $account, $op) {

  $grants = [];
  if ($op == 'update') {
    foreach (array_keys(\Drupal\node\Entity\NodeType::loadMultiple()) as $type) {
      if ($account->hasPermission('edit any ' . $type . ' content')) {
        $grants['edit any ' . $type . ' content'] = [0];
      }
      elseif ($account->id() && $account->hasPermission('edit own ' . $type . ' content')) {
        $grants['edit own ' . $type . ' content'] = [$account->id()];
      }
    }
  }
  return $grants;
}

/**
 * Implements hook_node_access_records().
 *
 * @see views_node_access_filter_node_grants()
 */
function views_node_access_filter_node_access_records(\Drupal\node\NodeInterface $node) {

  $type = $node->bundle();
  $grants = [];

  $grants[] = [
    'realm' => 'edit any ' . $type . ' content',
    'gid' => 0,
    'grant_view' => 0,
    'grant_update' => 1,
    'grant_delete' => 0,
  ];

  if ($owner_id = $node->getOwnerId()) {
    $grants[] = [
      'realm' => 'edit own ' . $type . ' content',
      'gid' => $owner_id,
      'grant_view' => 0,
      'grant_update' => 1,
      'grant_delete' => 0,
    ];
  }
  if ($node->isPublished()) {
    $grants[] = [
      'realm' => 'all',
      'gid' => 0,
      'grant_view' => 1,
      'grant_update' => 0,
      'grant_delete' => 0,
    ];
  }

  return $grants;

}