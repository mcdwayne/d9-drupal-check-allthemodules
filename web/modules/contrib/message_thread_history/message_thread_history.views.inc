<?php

/**
 * @file
 * Provide views data for message_thread_history.module.
 */

/**
 * Implements hook_views_data().
 */
function message_thread_history_views_data() {
  $data['message_thread_history']['table']['group'] = t('Message Thread');

  // Explain how this table joins to others.
  $data['message_thread_history']['table']['join'] = [
    // Directly links to message_thread table.
    'message_thread_field_data' => [
      'table' => 'message_thread_history',
      'left_field' => 'thread_id',
      'field' => 'thread_id',
      'extra' => [
        ['field' => 'uid', 'value' => '***CURRENT_USER***', 'numeric' => TRUE],
      ],
    ],
  ];

  $data['message_thread_history']['timestamp'] = [
    'title' => t('Is new message_thread'),
    'field' => [
      'id' => 'message_thread_history_timestamp',
      'help' => t('Show a marker if the message_thread is new or updated.'),
    ],
    'filter' => [
      'help' => t('Show only message threads that are new or updated.'),
      'id' => 'message_thread_history_timestamp',
    ],
  ];

  return $data;
}
