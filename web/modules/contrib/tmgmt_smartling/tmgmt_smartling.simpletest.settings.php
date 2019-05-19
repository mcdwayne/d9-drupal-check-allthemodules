<?php

/**
 * Settings for Smartling test project for simple tests.
 */

$settings = [
  'auto_accept' => TRUE,
  'settings[project_id]' => 'PROJECT_ID',
  'settings[user_id]' => 'USER_ID',
  'settings[token_secret]' => 'TOKEN_SECRET',
  'settings[contextUsername]' => 'DRUPAL_ADMIN_USER_NAME',
  'settings[context_silent_user_switching]' => FALSE,
  'settings[context_skip_host_verifying]' => FALSE,
  'settings[retrieval_type]' => 'pseudo',
  'settings[auto_authorize_locales]' => TRUE,
  'settings[callback_url_use]' => FALSE,
  'settings[callback_url_host]' => '',
  'settings[scheme]' => 'public',
  'settings[custom_regexp_placeholder]' => '(@|%|!)[\w-]+',
  'settings[enable_smartling_logging]' => FALSE,
  'settings[enable_notifications]' => TRUE,
  'settings[async_mode]' => FALSE,
  'settings[enable_basic_auth]' => FALSE,
  'settings[basic_auth][login]' => '',
  'settings[basic_auth][password]' => '',
];
