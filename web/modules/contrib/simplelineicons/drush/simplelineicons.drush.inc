<?php

/**
 * @file
 * Drush integration for simplelineicons.
 */

/**
 * Implementation of hook_drush_command().
 *
 * In this hook, you specify which commands your drush module makes available,
 * what it does and description.
 *
 * Notice how this structure closely resembles how you define menu hooks.
 *
 * @See drush_parse_command() for a list of recognized keys.
 *
 * @return array
 *   An associative array describing your command(s).
 */
function simplelineicons_drush_command() {
  $items = [];

  $items['sli-download'] = [
    'callback'    => 'simplelineicons_drush_lib_download',
    'description' => dt('Downloads the required Simple Line Icons library from https://github.com/thesabbir/simple-line-icons'),
    'aliases'     => [
      'slidl',
    ],
    'arguments'   => [
      'path' => dt('Optional. A path to the simplelineicons module. If omitted Drush will use the default location.'),
    ],
  ];

  return $items;
}

/**
 * Implementation of hook_drush_help().
 *
 * This function is called whenever a drush user calls
 * 'drush help <name-of-your-command>'
 *
 * @param
 *   A string with the help section (prepend with 'drush:')
 *
 * @return
 *   A string with the help text for your command.
 */
function simplelineicons_drush_help($section) {
  switch ($section) {
    case 'drush:sli-download':
      return dt("Downloads the required Simple Line Icons library from "
        . "https://github.com/thesabbir/simple-line-icons.");
  }
}

/**
 * Example drush command callback.
 *
 * This is where the action takes place.
 *
 * In this function, all of Drupal's API is (usually) available, including
 * any functions you have added in your own modules/themes.
 *
 * To print something to the terminal window, use drush_print().
 */
function simplelineicons_drush_lib_download() {
  $args = func_get_args();

  if (isset($args[0])) {
    $path = $args[0];
  }
  else {
    // We have dependencies on libraries module so no need to check for that.
    // TODO: any way to get path for libraries directory?
    // Just in case if it is site specific? e.g. sites/domain.com/libraries?
    $path = DRUPAL_ROOT . '/sites/all/libraries/simple-line-icons';
  }

  // Create the path if it does not exist yet. Added substr check for preventing
  // any wrong attempts or hacks!
  if (substr($path, -11) == 'simple-line-icons' && !is_dir($path)) {
    drush_mkdir($path);
  }

  if (is_dir($path . '/css')) {
    drush_log(dt('Simple Line Icons already present at @path. No download required.', [
      '@path' => $path,
    ]), 'ok');
  }
  elseif (drush_op('chdir', $path) &&
    drush_shell_exec('wget ' . SIMPLELINEICONS_DOWNLOAD_URL . '  -O simple-line-icons.zip') &&
    drush_shell_exec('unzip simple-line-icons.zip') &&
    drush_shell_exec('mv simple-line-icons-*/* .') &&
    drush_shell_exec('rm -rf simple-line-icons-*')
  ) {
    drush_log(dt('The Simple Line Icons library has been downloaded to @path', [
      '@path' => $path,
    ]), 'success');
  }
  else {
    drush_log(dt('Drush was unable to download the Simple Line Icons library to @path', [
      '@path' => $path,
    ]), 'error');
  }
}

/**
 * Implements drush_MODULE_post_COMMAND().
 */
function drush_simplelineicons_post_pm_enable() {
  $extensions = func_get_args();

  // Deal with comma delimited extension list.
  if (strpos($extensions[0], ',') !== FALSE) {
    $extensions = explode(',', $extensions[0]);
  }

  if (in_array('simplelineicons', $extensions) && !drush_get_option('skip')) {
    simplelineicons_drush_lib_download();
  }
}
