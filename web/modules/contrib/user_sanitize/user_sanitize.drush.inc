<?php

/**
 * @file
 * Contains cqc_webform_question_metadata.module.
 */

/**
 * Implements hook_drush_command().
 */
function user_sanitize_drush_command() {
  $items = [];
  $items['user-sanitize'] = [
    'description' => 'Sanitize users',
    'arguments' => [],
    'drupal dependencies' => ['user_sanitize'],
    'aliases' => ['ussan'],
  ];
  return $items;
}

/**
 * Implements drush_MODULE_ID_COMMAND_ID().
 */
function drush_user_sanitize() {
  drush_print('Starting user sanitization.');
  user_sanitize_trigger_sanitization(TRUE);
  drush_print('User sanitization complete.');
}
