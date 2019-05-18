<?php

/**
 * Implements hook_drush_command().
 */
function node_access_rebuild_progressive_drush_command() {

  $items['node-access-rebuild-progressive'] = array(
    'description' => "Fully rebuild node access.",
    'callback' => 'drush_node_access_rebuild_progressive_rebuild',
  );

  return $items;
}

/**
 * Rebuilds the node access grants table.
 */
function drush_node_access_rebuild_progressive_rebuild() {
  _drush_node_access_rebuild_progressive_rebuild()
}