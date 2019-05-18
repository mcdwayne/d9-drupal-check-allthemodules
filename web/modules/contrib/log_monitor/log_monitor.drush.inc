<?php
/**
 * Created by PhpStorm.
 * User: mike
 * Date: 10/11/17
 * Time: 2:23 PM
 */


/**
 * Implements hook_drush_command().
 */
function log_monitor_drush_command() {
  $items['log-monitor-process-storage'] = array(
    'description' => dt('Process items in log queue.'),
    'aliases' => array('lmps'),
    'bootstrap' => DRUSH_BOOTSTRAP_DRUPAL_FULL,
    'core' => array('8+'),
  );
  return $items;
}


function drush_log_monitor_process_storage() {
  $log_monitor_storage_manager = Drupal::service('log_monitor.storage_manager');
  $log_monitor_storage_manager->processLogQueue();
}