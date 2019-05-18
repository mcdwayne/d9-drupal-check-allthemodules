<?php

/**
 * Implements hook_drush_command().
 *
 * @return array
 */
function browser_refresh_drush_command() {
  $items['browser-refresh-start'] = array(
    'description' => 'Start browser refresh and keep it running.',
    'aliases' => array('brstart'),
  );
  $items['browser-refresh-stop'] = array(
    'description' => 'Stop browser refresh.',
    'aliases' => array('brstop'),
  );
  $items['browser-refresh-status'] = array(
    'description' => 'Get the status of browser refresh.',
    'aliases' => array('brstatus'),
  );
  $items['browser-refresh-restart'] = array(
    'description' => 'Stop and start browser refresh and keep it running.',
    'aliases' => array('brrestart'),
  );

  return $items;
}

/**
 * Callback to start browser refresh.
 */
function drush_browser_refresh_start() {
  \Drupal::service('browser_refresh.service')->start();
}

/**
 * Callback to stop browser refresh.
 */
function drush_browser_refresh_stop() {
  \Drupal::service('browser_refresh.service')->stop();
}

/**
 * Callback to get the status browser refresh.
 */
function drush_browser_refresh_status() {
  \Drupal::service('browser_refresh.service')->isActive(TRUE);
}

/**
 * Callback to re-start browser refresh.
 */
function drush_browser_refresh_restart() {
  \Drupal::service('browser_refresh.service')->restart();
}
