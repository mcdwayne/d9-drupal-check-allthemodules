<?php

/**
 * Implements hook_views_data_alter().
 */
function scheduling_node_views_data_alter(array &$data) {

  // Add views field for scheduling_mode field.
  $data['node']['scheduling_mode'] = [
    'title' => t('Scheduling mode'),
    'field' => [
      'title' => t('Scheduling mode'),
      'help' => t('Shows the configured scheduling mode.'),
      'id' => 'scheduling_mode',
    ],
  ];

  // Add views field for scheduling_value field.
  $data['node']['scheduling_value'] = [
    'title' => t('Scheduling value'),
    'field' => [
      'title' => t('Scheduling value'),
      'help' => t('Shows the configured scheduling value.'),
      'id' => 'scheduling_value',
    ],
  ];
}
