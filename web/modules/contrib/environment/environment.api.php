<?php
/**
 * @file
 * Hooks provided by Environment.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * React to an environment state change.
 *
 * Use this hook to specify changes to your site configuration depending on
 * what kind of environment the site is operating in. For example, production
 * environments should not have developer/site-builder oriented modules enabled,
 * such as administrative UI modules.
 *
 * When defining your state change actions, be careful to account for a given
 * state always consisting of the same behaviors and configuration, regardless
 * of how it returns to that state (which previous environment it was in.) Be
 * careful that you do not *disable* any modules in one environment that
 * implement a necessary instance of hook_environment_switch().
 *
 * @param string $target_env
 *   The name of the environment being activated.
 * @param string $current_env
 *   The name of the environment being deactivated.
 *
 * @return string
 *   String summarizing changes made for drush user.
 */
function hook_environment_switch($target_env, $current_env) {
  // Declare each optional development-related module.
  $devel_modules = array(
    'devel',
    'devel_generate',
    'devel_node_access',
  );

  switch ($target_env) {
    case 'production':
      module_disable($devel_modules);
      drupal_set_message('Disabled development modules');
      return;

    case 'development':
      module_enable($devel_modules);
      drupal_set_message('Enabled development modules');
      return;
  }
}

/**
 * @} End of "addtogroup hooks".
 */
