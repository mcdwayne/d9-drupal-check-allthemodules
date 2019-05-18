<?php

/**
 * @file
 * Provide views data for decoupled_auth.module.
 */

/**
 * Implements hook_views_data_alter().
 */
function decoupled_auth_views_data_alter(&$data) {
  $data['users']['decoupled'] = [
    'title' => t('Decoupled'),
    'help' => t('Whether this user is decoupled or not.'),
    'field' => [
      'id' => 'user_decoupled',
    ],
    'filter' => [
      'id' => 'user_decoupled',
    ],
  ];
}
