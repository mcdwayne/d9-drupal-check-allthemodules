<?php

/**
 * @file
 * Page callbacks and utility functions for IP Anonymize module.
 */

/**
 * Anonymize IP addresses which have exceeded the retention period.
 */
function ip_anon_scrub() {
  foreach (ip_anon_tables() as $table => $columns) {
    $rows = \Drupal::database()->update($table)
      ->fields([$columns['hostname'] => 0])
      ->condition($columns['timestamp'], REQUEST_TIME - \Drupal::config('ip_anon.settings')->get("period_$table"), '<')
      ->execute();
    if (!empty($columns['callback']) && $rows) {
      $columns['callback']();
    }
  }
}

/**
 * Default array of hostname and timestamp columns.
 */
function ip_anon_columns() {
  $columns = ['hostname', 'timestamp'];
  return array_combine($columns, $columns);
}

/**
 * Array of tables and columns which store hostnames and timestamps.
 *
 * Modules may add tables by implementing hook_ip_anon_alter().
 */
function ip_anon_tables() {
  $tables = ['sessions' => ip_anon_columns()];
  \Drupal::moduleHandler()->alter('ip_anon', $tables);
  return $tables;
}

/**
 * Implements hook_ip_anon_alter() for comment module.
 */
function comment_ip_anon_alter(&$tables) {
  $tables['comment_field_data'] = [
    'hostname' => 'hostname',
    'timestamp' => 'changed',
  ];
}

/**
 * Implements hook_ip_anon_alter() for dblog module.
 */
function dblog_ip_anon_alter(&$tables) {
  $tables['watchdog'] = ip_anon_columns();
}

/**
 * Implements hook_ip_anon_alter() for Commerce Order module.
 */
function commerce_order_ip_anon_alter(&$tables) {
  $tables['commerce_order'] = [
    'hostname' => 'ip_address',
    'timestamp' => 'changed',
  ];
}

/**
 * Implements hook_ip_anon_alter() for Login History module.
 */
function login_history_ip_anon_alter(&$tables) {
  $tables['login_history'] = [
    'hostname' => 'hostname',
    'timestamp' => 'login',
  ];
}

/**
 * Implements hook_ip_anon_alter() for Ubercart Order module.
 */
function uc_order_ip_anon_alter(&$tables) {
  $tables['uc_orders'] = [
    'hostname' => 'host',
    'timestamp' => 'changed',
  ];
}

/**
 * Implements hook_ip_anon_alter() for User IP Log module.
 */
function uiplog_ip_anon_alter(&$tables) {
  $tables['uiplog'] = array(
    'hostname' => 'ip',
    'timestamp' => 'timestamp',
  );
}

/**
 * Implements hook_ip_anon_alter() for Voting API module.
 */
function votingapi_ip_anon_alter(&$tables) {
  $tables['votingapi_vote'] = [
    'hostname' => 'vote_source',
    'timestamp' => 'timestamp',
  ];
}

/**
 * Implements hook_ip_anon_alter() for webform module.
 */
function webform_ip_anon_alter(&$tables) {
  $tables['webform_submission'] = [
    'hostname' => 'remote_addr',
    'timestamp' => 'changed',
    'callback' => function () {
      \Drupal::entityTypeManager()->getStorage('webform_submission')->resetCache();
    },
  ];
}

/**
 * Implements hook_ip_anon_alter() for yamlform module.
 */
function yamlform_ip_anon_alter(&$tables) {
  $tables['yamlform_submission'] = [
    'hostname' => 'remote_addr',
    'timestamp' => 'changed',
  ];
}
