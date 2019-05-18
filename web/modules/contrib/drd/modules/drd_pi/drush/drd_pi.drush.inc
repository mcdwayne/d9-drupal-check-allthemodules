<?php

/**
 * @file
 * Drush command file for Drush 8.
 */

/**
 * Implements hook_drush_command().
 */
function drd_pi_drush_command() {
  $items = [
    'drd-pi-sync' => [],
  ];

  return $items;
}

/**
 * Callback to execute drd-pi-sync Drush command.
 */
function drush_drd_pi_sync() {
  _drd_drush_action_system('drd_action_pi_sync');
}
