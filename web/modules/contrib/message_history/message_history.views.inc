<?php

/**
 * @file
 * Provide views data for message_history.module.
 */

/**
 * Implements hook_views_data().
 */
function message_history_views_data() {
  $data['message_history']['table']['group'] = t('Message');

  // Explain how this table joins to others.
  $data['message_history']['table']['join'] = [
    // Directly links to message table.
    'message_field_data' => [
      'table' => 'message_history',
      'left_field' => 'mid',
      'field' => 'mid',
      'extra' => [
        ['field' => 'uid', 'value' => '***CURRENT_USER***', 'numeric' => TRUE],
      ],
    ],
  ];

  $data['message_history']['timestamp'] = [
    'title' => t('Is new message'),
    'field' => [
      'id' => 'message_history_timestamp',
      'help' => t('Show a marker if the message is new or updated.'),
    ],
    'filter' => [
      'help' => t('Show only messages that are new or updated.'),
      'id' => 'message_history_timestamp',
    ],
  ];

  return $data;
}
