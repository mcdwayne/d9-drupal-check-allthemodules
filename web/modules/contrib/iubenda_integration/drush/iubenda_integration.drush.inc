<?php

/**
 * @file
 * Drush integration for iubenda.
 */

/**
 * The Iubenda PHP class URI.
 */
define('IUBENDA_INTEGRATION_DOWNLOAD_URI',
  'http://simon.s3.iubenda.com/iubenda-cookie-class/iubenda-cookie-class.zip');

/**
 * Implements hook_drush_command().
 *
 * In this hook, you specify which commands your
 * drush module makes available, what it does and
 * description.
 *
 * Notice how this structure closely resembles how
 * you define menu hooks.
 *
 * See `drush topic docs-commands` for a list of recognized keys.
 */
function iubenda_integration_drush_command() {
  $items = [];

  // The key in the $items array is the name of the command.
  $items['iubenda-php-class'] = [
    'callback' => 'drush_iubenda_php_class',
    'description' => dt('Download and install the Iubenda PHP class.'),
    // No bootstrap.
    'bootstrap' => DRUSH_BOOTSTRAP_DRUSH,
    'arguments' => [
      'path' => dt("Optional. A path where to install the Iubenda PHP class."
        . "\nIf omitted Drush will use the default location."),
    ],
    'aliases' => ['ipc'],
  ];

  return $items;
}

/**
 * Implements hook_drush_help().
 *
 * This function is called whenever a drush user calls
 * 'drush help <name-of-your-command>'
 */
function iubenda_integration_drush_help($section) {
  switch ($section) {
    case 'drush:iubenda-php-class':
      return dt("Download and install the Iubenda PHP class from"
        . "\nhttps://www.iubenda.com/it/help/posts/1045, default location"
        . "\nis DRUPAL_ROOT/libraries");
  }
}

/**
 * Implements drush_MODULE_pre_pm_enable().
 */
function drush_iubenda_integration_pre_pm_enable() {
  $modules = drush_get_context('PM_ENABLE_MODULES');
  if (in_array(IUBENDA_INTEGRATION_MODULE_NAME, $modules) && !drush_get_option('skip')) {
    drush_iubenda_php_class();
  }
}

/**
 * Command to download the Iubenda PHP class.
 */
function drush_iubenda_php_class() {
  $args = func_get_args();
  if (!empty($args[0])) {
    $path = $args[0];
  }
  else {
    $path = DRUPAL_ROOT . '/libraries';
  }

  // Create the path if it does not exist.
  if (!is_dir($path)) {
    drush_op('mkdir', $path);
    drush_log(dt('Directory @path was created', ['@path' => $path]), 'notice');
  }

  // Set the directory to the download location.
  $olddir = getcwd();
  chdir($path);

  // Download the zip archive.
  if ($filepath = drush_download_file(IUBENDA_INTEGRATION_DOWNLOAD_URI)) {
    $dirname = basename($filepath, '.zip');
    // Remove any existing Iubenda PHP class directory.
    if (is_dir($dirname)) {
      drush_delete_dir($dirname, TRUE);
      drush_log(dt('A existing Iubenda PHP class was deleted from @path',
        ['@path' => $path]), 'notice');
    }

    // Decompress the zip archive.
    drush_tarball_extract($filepath, $dirname);
  }

  if (is_dir($dirname)) {
    drush_log(dt('Iubenda PHP class has been installed in @path',
      ['@path' => $path]), 'success');
  }
  else {
    drush_log(dt('Drush was unable to install the Iubenda PHP class to @path',
      ['@path' => $path]), 'error');
  }

  // Set working directory back to the previous working directory.
  chdir($olddir);
}
