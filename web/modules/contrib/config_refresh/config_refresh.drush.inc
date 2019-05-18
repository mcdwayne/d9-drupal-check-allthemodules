<?php

/**
 * Implements hook_drush_command().
 */
function config_refresh_drush_command() {
  $items = [];

  $items['config-refresh-module'] = [
    'description' => 'Refreshes the entire config of a specified module.',
    'arguments' => [
      'module' => 'One or multiple modules to refresh',
    ],
    'aliases' => ['crm'],
  ];

  return $items;
}

function drush_config_refresh_module() {
  $modules = func_get_args();

  /** @var \Drupal\config_refresh\ConfigRefreshManager $config_refresh_manager */
  $config_refresh_manager = \Drupal::service('config_refresh.manager');

  foreach ($modules as $module_name) {
    $config_refresh_manager->refreshByModule($module_name);
  }
}
