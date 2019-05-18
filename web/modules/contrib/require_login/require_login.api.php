<?php

/**
 * @file
 * Documentation for require login API.
 */

/**
 * Alter login requirement checks. The $checks variable is a non-associative
 * array containing only boolean values. Login will be required if $checks
 * includes at least one TRUE boolean.
 *
 * @param array &$checks
 *   Boolean check values.
 */
function hook_require_login_authcheck_alter(&$checks) {
  $variable_1 = $variable_2 = 'hello-world';

  // Allow access if $variable_1 equals $variable_2.
  $checks[] = ($variable_1 == $variable_2);
}