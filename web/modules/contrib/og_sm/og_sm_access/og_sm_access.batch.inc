<?php
/**
 * @file
 * Batch operations and helpers.
 */

/**
 * Rebuild the node_access table for the group by setting a batch.
 *
 * @param object $site
 *   The Site node to rebuild the access records for.
 */
function og_sm_access_node_access_rebuild($site) {
  $params = array($site->nid);
  $file = drupal_get_path('module', 'og_sm_access') . '/og_sm_access.batch.inc';

  $batch = array(
    'title' => t('Handle Site published status change'),
    'operations' => array(
      array('og_sm_access_node_access_rebuild_batch', $params),
    ),
    'file' => $file,
  );

  batch_set($batch);
}

/**
 * Batch API to recalculate the Site content nodes node_access grants.
 *
 * @param int $site_nid
 *   The Site node id.
 * @param array $context
 *   Batch API context.
 */
function og_sm_access_node_access_rebuild_batch($site_nid, &$context) {
  _og_sm_access_node_access_batch_init($site_nid, $context);

  // Get the group memberships.
  $site_memberships = _og_sm_access_node_access_rebuild_get_memberships($site_nid, $context);
  if (!$site_memberships) {
    $context['finished'] = 1;
    return;
  }

  $search_api_exists = module_exists('search_api');

  // Rebuild the permissions for the node.
  foreach ($site_memberships as $site_membership) {
    $node = node_load($site_membership->etid);
    node_access_acquire_grants($node);

    // Reindex the node!
    if ($search_api_exists) {
      search_api_track_item_change('node', array($node->nid));
    }

    $context['sandbox']['progress']++;
    $context['sandbox']['last_id'] = $node->nid;
  }

  $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['total'];
}

/**
 * Initiate the batch.
 *
 * @param int $site_nid
 *   The Site node id.
 * @param array $context
 *   Batch API context.
 */
function _og_sm_access_node_access_batch_init($site_nid, &$context) {
  // Initiate the batch.
  if (empty($context['sandbox'])) {
    // Count relevant nodes.
    $query = new EntityFieldQuery();
    $total = $query
      ->entityCondition('entity_type', 'og_membership')
      ->propertyCondition('group_type', 'node')
      ->propertyCondition('entity_type', 'node')
      ->propertyCondition('gid', $site_nid, '=')
      ->count()->execute();

    $context['sandbox']['progress'] = 0;
    $context['sandbox']['last_id'] = 0;
    $context['sandbox']['total'] = $total;
  }
}

/**
 * Get the next set of site_memberships (og_membership) to process.
 *
 * @param int $site_nid
 *   The Site node id.
 * @param array $context
 *   Batch API context.
 *
 * @return array[OgMembership]
 *   Array of OgMembership objects.
 */
function _og_sm_access_node_access_rebuild_get_memberships($site_nid, &$context) {
  $limit = 50;

  // Retrieve the next batch.
  $query = new EntityFieldQuery();
  $result = $query
    ->entityCondition('entity_type', 'og_membership')
    ->propertyCondition('group_type', 'node')
    ->propertyCondition('entity_type', 'node')
    ->propertyCondition('gid', $site_nid)
    ->propertyCondition('etid', $context['sandbox']['last_id'], '>')
    ->propertyOrderBy('etid', 'ASC')
    ->range(0, $limit)
    ->execute();

  // Check if we found any memberships.
  if (!isset($result['og_membership'])) {
    return array();
  }

  return entity_load('og_membership', array_keys($result['og_membership']));
}
