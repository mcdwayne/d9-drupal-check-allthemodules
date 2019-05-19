<?php

/**
 * @file
 * Defines drush commands for stools.
 */

/**
 * Implements hook_drush_command().
 */
function stools_drush_command() {

  $items['re-deploy'] = [
    'description' => 'Disable and deploy a module.',
    'aliases' => ['red'],
    'arguments' => [
      'prefix' => 'Module prefix ex. gbs',
      'module' => 'Module machine name ex. page',
    ],
    'examples' => [
      'drush red prefix page' => 'Disables module and enables the deploy module',
      'drush re-deploy prefix page' => 'Disables module and enables the deploy module',
    ],
  ];

  $items['re-enable'] = [
    'description' => 'Uninstall and install a module.',
    'aliases' => ['ren'],
    'arguments' => [
      'module' => 'Module machine name ex. page',
    ],
    'examples' => [
      'drush ren module' => 'Disables module and enables the deploy module',
      'drush re-enable module' => 'Disables module and enables the deploy module',
    ],
  ];

  return $items;
}

/**
 * Callback for the re-deploy command.
 */
function drush_stools_re_deploy($prefix, $module) {
  $args = array_slice(drush_get_arguments(), 2);

  if (!isset($module)) {
    drush_print('You must have 2 arguments, ex. drush red gbs base');
    return;
  }

  foreach ($args as $arg) {
    $disable_module = $prefix . '_' . $arg;
    $deploy_module = $prefix . '_deploy';

    drush_print('Disabling ' . $disable_module . ' and enabling ' . $deploy_module);

    drush_invoke_process("@site", "dis", [$disable_module], ["--y"]);
  }

  drush_invoke_process("@site", "en", [$deploy_module], ["--y"]);

}

/**
 * Callback for the re-deploy command.
 */
function drush_stools_re_enable() {
  $args = array_slice(drush_get_arguments(), 1);

  // Uninstall everything.
  foreach ($args as $arg) {
    drush_print('Uninstalling ' . $arg);
    drush_invoke_process('@site', 'pm-uninstall', [$arg], ['--y']);
  }

  $args = array_reverse($args);
  foreach ($args as $arg) {
    drush_print('Enabling ' . $arg);
    drush_invoke_process('@site', 'pm-enable', [$arg], ['--y']);
  }

}
