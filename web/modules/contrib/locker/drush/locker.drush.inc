<?php

/**
 * @file
 * This is file was generated using Drush. DO NOT EDIT.
 *
 * @see drush locker-generate-commands
 * @see \Drupal\locker\Commands\DrushCliServiceBase::generate_commands_drush8
 */

/**
 * Implements hook_drush_command().
 */
function locker_drush_command() {
  return [
    'lock' => [
      'description' => 'Lock site.',
      'arguments' => [
        'passphrase'    => "Enter your desired passphrase to unlock the site.",
        'username'      => "Enter your desired username to unlock the site.",
        'password'      => "Enter your desired password to unlock the site.",
      ],
      'examples' => [
        'drush lock u username password' => 'Lock your Drupal site with username = username & password = password.',
        'drush lock passphrase' => 'Lock your Drupal site with passphrase = passphrase.',
      ],
      'aliases' => ['lock'],
    ],
    'unlock' => [
      'description' => 'Unlock your Drupal site.',
      'examples' => [
        'drush unlock' => 'Unlock your Drupal site.',
      ],
      'aliases' => ['unlock'],
    ],
  ];
}

/**
 * Implements drush_hook_lock().
 */
function drush_locker_lock($passphrase = '', $user = '', $pass = '') {
  return call_user_func_array([\Drupal::service('locker.cli_service'), 'drush_locker_lock'], func_get_args());
}

/**
 * Implements drush_hook_unlock().
 */
function drush_locker_unlock() {
  return call_user_func_array([\Drupal::service('locker.cli_service'), 'drush_locker_unlock'], func_get_args());
}
