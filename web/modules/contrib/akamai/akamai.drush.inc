<?php

/**
 * @file
 * Command-line tools for Akamai actions.
 */

/**
 * Implements hook_drush_command().
 */
function akamai_drush_command() {
  $items['akamai-clear-cpcode'] = [
    'description' => 'Akamai clear cpcode.',
    'arguments' => [
      'path' => 'A cpcode to clear. You can provide as many cpcodes you like.',
    ],
    'options' => [
      'action' => 'Clearing action type.',
      'domain' => 'The Akamai domain to use for cache clearing.',
    ],
    'aliases' => ['akcc'],
  ];
  $items['akamai-clear-url'] = [
    'description' => 'Akamai clear url.',
    'arguments' => [
      'path' => 'A path to clear. You can provide as many paths you like.',
    ],
    'options' => [
      'action' => 'Clearing action type.',
      'domain' => 'The Akamai domain to use for cache clearing.',
    ],
    'aliases' => ['akcu'],
  ];

  return $items;
}

/**
 * Callback function for ak-clear-cpcode command.
 *
 * Runs the akamai clear cpcode command.
 */
function drush_akamai_clear_cpcode($cpcode) {
  $action = drush_get_option('action', 'remove');
  $domain = drush_get_option('domain', 'production');
  $client = \Drupal::service('akamai.client.factory')->get();
  $client->setType('cpcode');
  $client->setAction($action);
  $client->setDomain($domain);
  $cpcodes = explode(' ', $cpcode);
  if ($client->purgeCpCodes($cpcodes)) {
    drush_print(dt('Akamai Cache Request has been made successfully, please allow 10 minutes for changes to take effect.'));
    drush_print(dt('Asked Akamai to purge: :cpcode', [':cpcode' => $cpcode]));
  }
}

/**
 * Callback function for ak-clear-url command.
 *
 * Runs the akamai clear url command.
 */
function drush_akamai_clear_url($path) {
  $action = drush_get_option('action', 'remove');
  $domain = drush_get_option('domain', 'production');
  $client = \Drupal::service('akamai.client.factory')->get();
  $client->setAction($action);
  $client->setDomain($domain);
  $paths = explode(' ', $path);
  if ($client->purgeUrls($paths)) {
    drush_print(dt('Akamai Cache Request has been made successfully, please allow 10 minutes for changes to take effect.'));
    drush_print(dt('Asked Akamai to purge: :uri', [':uri' => $path]));
  }
}
