<?php

/**
 * @file
 * gnode_field.views.inc
 */

/**
 * Implements hook_views_data_alter().
 */
function gnode_field_views_data_alter(array &$data) {
  $data['groups_field_data']['gnode_field'] = [
    'filter' => [
      'title' => t('Group Node Filter'),
      'help' => t('Filters nodes by their group'),
      'id' => 'gnode_field_filter',
    ],
  ];
}
