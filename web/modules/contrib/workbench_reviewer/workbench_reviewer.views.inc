<?php

/**
 * @file
 * Content moderation views file.
 */

/**
 * Implements hook_views_data_alter().
 */
function workbench_reviewer_views_data_alter(&$data) {
  $data['node_field_revision']['nodes_moderation_reviewer'] = [
    'title' => t('Node Workbench Reviewer'),
    'argument' => [
      'title' => t('Node Workbench Reviewer'),
      'help' => t('Specify a user that can be assigned as a reviewer of a node.'),
      'field' => 'workbench_reviewer',
      'id' => 'workbench_reviewer_node_reviewer',
    ],
  ];
}
