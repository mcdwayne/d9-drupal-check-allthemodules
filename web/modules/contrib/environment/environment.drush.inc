<?php
/**
 * @file
 * Executes the environment_switch capabilities.
 */

/**
 * Implements hook_drush_command().
 */
function environment_drush_command() {
  $items = array();

  $items['environment-list'] = array(
    'description' => 'Show all environments.',
    'drupal dependencies' => array('environment'),
    'aliases' => array('env-list'),
  );

  $items['environment-current'] = array(
    'description' => 'Show the current environment state.',
    'drupal dependencies' => array('environment'),
    'aliases' => array('env', 'env-current', 'environment'),
  );

  $items['environment-switch'] = array(
    'description' => 'Switch the environment to specified state.',
    'drupal dependencies' => array('environment'),
    'arguments' => array(
      'target_env' => 'The target environment to which the site will be switched.',
    ),
    'options' => array(
      'force' => 'Whether an environment switch should be forced if the target enviornment is the same as the current environment.',
      'nocacheclear' => 'Switching environments clears the cache by default, this stops that.',
    ),
    'examples' => array(
      'drush environment-switch development' => 'Switch the environment to development.',
      'drush env-switch --force production' => 'Force the environment to switch to production even if the current environment already is production.',
      'drush env-switch --nocacheclear production' => 'Switch environments but do not clear cache.',
    ),
    'aliases' => array('env-switch'),
  );

  return $items;
}

/**
 * Implements hook_drush_help().
 */
function environment_drush_help($section) {
  switch ($section) {
    case 'meta:environment:title':
      return dt('Environment commands');

    case 'meta:environment:summary':
      return dt('Review and control the site environment.');

    case 'drush:environment':
      return dt("Shows the current environment.");

    case 'drush:environment-switch':
      return dt("Switches the environment to the target environment.");
  }
}

/**
 * Implements drush_hook_COMMAND for environment.
 *
 * Render all current environments with full description.
 */
function drush_environment_list() {
  $environments = environment_load();

  $drush_context = drush_get_context('DRUSH_PIPE') ? 'drush_print_pipe' : 'drush_print_table';

  if (isset($environments)) {
    $items = array();
    foreach ($environments as $environment) {
      $items[$environment->get('id')] = $environment->get('label');
    }

    if ($drush_context == 'drush_print_pipe') {
      drush_print_pipe($items);
    }
    else {
      drush_print_table(drush_key_value_to_array_table($items));
    }
  }
  return;
}

/**
 * Implements drush_hook_COMMAND for environment.
 *
 * Render current environment with full description.
 *
 * @return array
 *   Array of strings describing the environment(s).
 */
function drush_environment_current() {
  $environment_id = \Drupal::config('environment.settings')->get('environment');

  $environment = environment_load($environment_id);

  if (isset($environment)) {
    return drush_format($environment->get('label'));
  }
  return;
}

/**
 * Implements drush_hook_COMMAND_validate() for environment_switch.
 */
function drush_environment_switch_validate($target_env = NULL) {
  $environments = environment_load();
  if (empty($environments)) {
    return drush_set_error('DRUSH_ENVIRONMENT_ERROR', dt('There are no defined environments available.'));
  }
}

/**
 * Implements drush_hook_COMMAND for environment_switch.
 */
function drush_environment_switch($target_env = NULL) {
  if ($target_env && !array_key_exists($target_env, environment_load())) {
    drush_set_error('DRUSH_ENVIRONMENT_ERROR', dt('Invalid environment specified.'));
    $target_env = NULL;
  }
  if (!$target_env) {
    $environments = environment_load();
    $options = array();
    foreach ($environments as $name => $env) {
      $options[$name] = environment_drush_render_environment($env);
    }
    $target_env = drush_choice($options);
  }
  if (!$target_env) {
    return;
  }

  $force = drush_get_option('force');
  $no_cache_clear = drush_get_option('nocacheclear', FALSE);

  drush_print(dt("Switching the environment to !environment", array('!environment' => $target_env)));
  if (!drush_get_context('DRUSH_SIMULATE')) {
    $result = environment_switch($target_env, $force, !$no_cache_clear);
  }
  else {
    $result = TRUE;
  }

  if ($result) {
    drupal_set_message('Done.', 'success');
  }
  else {
    drupal_set_message('Failed.', 'error');
  }
}

/**
 * Render the specified environment definition as a descriptive one-liner.
 *
 * @param array $state
 *   Array defining an environment state.
 * @param bool $show_workflow
 *   Optional; default to TRUE. If there are more than one workflows defined in
 *   the system, will prefix the return with the workflow's label. If set to
 *   FALSE this is skipped. This is separate from $verbose because you could
 *   want a verbose description in a workflow-specific context.
 * @param bool $verbose
 *   Optional; defaults to FALSE. If TRUE, will include the environment
 *   description.
 *
 * @return string
 *   String describing the specified environment.
 */
function environment_drush_render_environment($environment, $verbose = FALSE) {

  return $environment->get('label') . ' (' . $environment->get('id') . ')';
}
