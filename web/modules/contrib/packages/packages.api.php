<?php

/**
 * @file
 * Document all supported API hooks.
 */

 /**
  * Implements hook_packages_info_alter().
  *
  * Alter the Package plugin definitions.
  *
  * @param array &$definitions
  *   An array of Package plugin definitions.
  */
function hook_packages_info_alter(array &$definitions) {
  $definitions['my_package_id']['label'] = t('New label');
}

/**
 * Implements hook_packages_states_alter().
 *
 * Alter the Package states for the current user. This is executed once they
 * are loaded from the database and built in the packages service.
 *
 * @param array &$states
 *   An array of PackageState objects representing the states for the current
 *   user.
 */
function hook_packages_states_alter(array &$states) {
  $states['login_greeting']->setAccess(FALSE);
}
