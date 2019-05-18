<?php

/**
 * @file
 * Dashboard Connector drush commands.
 */

use Drupal\dashboard_connector\Dashboard;
use Drush\Log\LogLevel;

/**
 * Implements hook_drush_command().
 */
function dashboard_connector_drush_command() {
  $items = [];
  $items['dashboard-send'] = [
    'description' => 'Send a snapshot to the Dashboard API.',
    'aliases' => ['d-send'],
  ];
  return $items;
}

/**
 * Implements drush_hook_command().
 */
function drush_dashboard_connector_dashboard_send() {
  try {
    $snapshot = Dashboard::snapshotBuilder()->buildSnapshot();
    Dashboard::connector()->sendSnapshot($snapshot);
    drush_log(dt('Successfully sent status snapshot'), LogLevel::SUCCESS);
  }
  catch (\Exception $e) {
    drush_log(dt('Failed to send snapshot'), LogLevel::ERROR, $e->getMessage());
  }
}
