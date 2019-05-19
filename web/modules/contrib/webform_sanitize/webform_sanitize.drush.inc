<?php

/**
 * @file
 * Contains cqc_webform_question_metadata.module.
 */

/**
 * Implements hook_drush_command().
 */
function webform_sanitize_drush_command() {
  $items = [];
  $items['webform-sanitize'] = [
    'description' => 'Sanitize webform submissions',
    'arguments' => [
      'webform_id' => 'One webform id to sanitize',
    ],
    'drupal dependencies' => ['webform_sanitize'],
    'aliases' => ['wfsan'],
  ];
  return $items;
}

/**
 * Implements drush_MODULE_ID_COMMAND_ID().
 */
function drush_webform_sanitize($webform_id_param = NULL) {
  webform_sanitize_trigger_sanitization($webform_id_param);
}
