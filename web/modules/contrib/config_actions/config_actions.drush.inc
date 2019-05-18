<?php

/**
 * @file
 * Config Actions drush integration.
 */

use Drupal\config_actions\ConfigActionsServiceInterface;

/**
 * Implements hook_drush_command().
 */
function config_actions_drush_command() {
  $items = array();

  $items['config-actions-list'] = array(
    'description' => 'Display a list of available actions in modules.',
    'aliases' => array('cal'),
    'arguments' => array(
      'module' => 'Optional name of module to filter the list.',
      'file' => 'Optional name of file within module to list.',
    ),
  );

  $items['config-actions-run'] = array(
    'description' => 'Execute actions within a module.',
    'aliases' => array('car'),
    'arguments' => array(
      'module' => 'Name of module containing the action.  If omitted, all actions in all modules are executed.',
      'file' => 'Optional name of file within module containing action. If omitted, all actions in the module are executed.',
      'action' => 'Optional name of action to execute.  If omitted, all actions in the file are executed.',
    ),
  );

  return $items;
}

/**
 * Drush command callback for config-actions-list.
 */
function drush_config_actions_list() {
  /** @var \Drupal\config_actions\ConfigActionsServiceInterface $config_actions */
  $config_actions = \Drupal::service('config_actions');

  $args = func_get_args();
  $module_name = array_shift($args);
  $file = array_shift($args);

  $list = $config_actions->listAll($module_name, $file);
  if (empty($list)) {
    drush_print(dt('No actions found.'));
  }
  else {
    foreach ($list as $module => $files) {
      drush_print(dt('Module: @name', array('@name' => $module)));
      foreach ($files as $filename => $actions) {
        drush_print(dt('  File: @file', array('@file' => $filename)));
        foreach ($actions as $action_id => $action) {
          if (!empty($action_id)) {
            drush_print(dt('    @action', array('@action' => $action_id)));
          }
        }
      }
    }
  }
}

/**
 * Drush command callback for config-actions-run.
 */
function drush_config_actions_run() {
  /** @var \Drupal\config_actions\ConfigActionsServiceInterface $config_actions */
  $config_actions = \Drupal::service('config_actions');

  $args = func_get_args();
  $module_name = array_shift($args);
  $file = array_shift($args);
  $action_id = array_shift($args);

  $result = $config_actions->importAction($module_name, $action_id, $file);
  if (empty($result)) {
    drush_print(dt('No actions were executed.'));
  }
  else {
    foreach ($result as $source => $config) {
      if (is_null($config)) {
        drush_print(dt('  Action: @action - SKIPPED', array('@action' => $source)));
      }
      else {
        drush_print(dt('  Action: @action', array('@action' => $source)));
      }
    }
  }
}
