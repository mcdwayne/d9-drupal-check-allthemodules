<?php

/**
 * Implements hook_drush_command_alter().
 */
function role_memory_limit_drush_command_alter(&$command) {

  // Sets the PHP memory limit for drush.
  $config = drush_config_get_object('role_memory_limit.config');
  if (empty($config['drush'])) {
    return;
  }
  ini_set('memory_limit', $config['drush'] . 'M');
}
