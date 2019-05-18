<?php

/**
 * Alter the value of the current working directory for npm scripts.
 *
 * This is where package.json and node_modules are.
 *
 * @param string $cwd
 *   Current working directory for the executable.
 */
function hook_npm_working_dir_alter(&$cwd) {
  $cwd = DRUPAL_ROOT;
}
