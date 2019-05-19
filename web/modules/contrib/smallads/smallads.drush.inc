<?php

/**
 * @file
 * Generate smallads via drush framework.
 */

/**
 * Implements hook_drush_command().
 */
function smallads_drush_command() {
  $items['generate-smallads'] = array(
    'callback' => 'drush_devel_generate',
    'callback arguments' => array(
      'plugin_id' => 'smallad',
    ),
    'description' => 'Create small ads.',
    'arguments' => array(
      'num' => 'Number of small ads to generate.',
      'type' => 'Just generate ads of the given type',
    ),
    'options' => array(
      'kill' => 'Delete all users before generating new ones.',
    ),
    'aliases' => array('gensa'),
    'drupal dependencies' => [
      'modules' => [
        'devel-generate',
      ],
    ],
  );
  return $items;
}

/**
 * Implements drush_hook_COMMAND_validate().
 */
function drush_devel_generate_generate_smallads_validate() {
  // Array of "Callback arguments" and "command line args".
  $params = func_get_args();
  _drush_plugin_validate($params);
}
