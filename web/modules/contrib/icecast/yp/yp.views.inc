<?php

/**
 * @file
 * Provide views data and handlers for yp.module.
 */

/**
 * Implements hook_views_data().
 */
function yp_views_data() {
  $data['yp_stream']['table']['group'] = t('Icecast YP');
  $data['yp_stream']['table']['base'] = [
    'field' => 'sid',
    'title' => t('Icecast YP'),
    'help' => t('Icecast streams listed by the Icecast YP directory module.'),
  ];
  $schema = drupal_get_module_schema('yp', 'yp_stream');
  foreach ($schema['fields'] as $key => $field) {
    $data['yp_stream'][$key] = [
      'title' => $field['description'],
      'help' => $field['description'] . '.',
      'field' => [
        'id' => 'standard',
        'click sortable' => TRUE,
      ],
    ];
  }
  $data['yp_stream']['server_name']['field']['id'] = 'yp_server_name';
  $data['yp_stream']['current_song']['field']['id'] = 'yp_current_song';
  $data['yp_stream']['listen_url']['field']['id'] = 'yp_listen_url';
  $data['yp_stream']['server_type']['field']['id'] = 'yp_server_type';
  $data['yp_stream']['bitrate']['field']['id'] = 'yp_bitrate';
  $data['yp_stream']['listeners']['field']['id'] = 'numeric';
  $data['yp_stream']['max_listeners']['field']['id'] = 'numeric';
  $data['yp_stream']['last_touch']['field']['id'] = 'date';
  return $data;
}
