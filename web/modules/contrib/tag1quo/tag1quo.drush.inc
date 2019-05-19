<?php

/**
 * @file
 * Tag1 Quo Drush commands.
 */

use Drupal\tag1quo\Heartbeat;

/**
 * Implements hook_drush_command().
 */
function tag1quo_drush_command() {
  $items['quo-heartbeat'] = array(
    'bootstrap' => DRUSH_BOOTSTRAP_DRUPAL_FULL,
    'description' => "Send a heartbeat to Tag1 Quo.",
    'examples' => array(
      'drush send-heartbeat' => 'Send a heartbeat to Tag1 Quo',
    ),
    'options' => array(
      'api-token' => dt('Your Tag1 Quo API token.'),
      'debug' => dt('Toggles whether to display/log debug information.'),
    ),
    'aliases' => array('quo', 'heartbeat'),
  );
  return $items;
}

/**
 * Implementation of drush quo-heartbeat
 */
function drush_tag1quo_quo_heartbeat() {
  $heartbeat = Heartbeat::manual();

  if ($apiToken = drush_get_option('api-token')) {
    $heartbeat->setApiToken($apiToken);
  }

  $debug = drush_get_option('debug');
  if ($debug !== NULL) {
    $heartbeat->setDebugMode($debug);
  }

  $heartbeat->send();
}
