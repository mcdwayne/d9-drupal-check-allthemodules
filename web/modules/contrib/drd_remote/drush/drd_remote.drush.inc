<?php

/**
 * Implements hook_drush_command().
 *
 * @return array
 */
function drd_remote_drush_command() {
  $items = [];

  $items['drd-remote-setup'] = [
    'arguments' => [
      'token' => 'Base64 encoded and serialized array of all variables required such that DRD can communicate with this domain in the future',
    ],
  ];

  return $items;
}

/**
 * Drush command to configure this domain for communication with a DRD instance.
 *
 * @param $token
 *
 * @see drd_remote_setup()
 */
function drush_drd_remote_setup($token) {
  $service = \Drupal::service('drd_remote.setup');
  $service->run($token);
}
