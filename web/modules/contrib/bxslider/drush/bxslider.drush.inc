<?php

/**
 * @file
 * Drush integration for bxslider.
 */

/**
 * The bxSlider plugin URI.
 */
define('BXSLIDER_DOWNLOAD_URI', 'https://github.com/stevenwanderski/bxslider-4/archive/v4.2.15.zip');
define('BXSLIDER_DOWNLOAD_PREFIX', 'bxslider-');

/**
 * Implements hook_drush_command().
 */
function bxslider_drush_command() {
  $items = [];

  // The key in the $items array is the name of the command.
  $items['bxslider-plugin'] = [
    'callback' => 'bxslider_drush_plugin_setup',
    'description' => dt('Download and install the bxSlider plugin.'),
    // No bootstrap.
    'bootstrap' => DRUSH_BOOTSTRAP_DRUSH,
    'arguments' => [
      'path' => dt('Optional. A path where to install the bxSlider plugin. If omitted Drush will use the default Libraries location.'),
    ],
    'aliases' => ['bxsliderplugin'],
  ];

  return $items;
}

/**
 * Implements hook_drush_help().
 */
function bxslider_drush_help($section) {
  switch ($section) {
    case 'drush:bxslider-plugin':
      return dt('Download and install the bxSlider plugin. Default location is the Libraries directory.');
  }
}

/**
 * Command to download the bxSlider plugin.
 */
function bxslider_drush_plugin_setup() {
  $args = func_get_args();
  if (!empty($args[0])) {
    $path = $args[0];
  }
  else {
    $path = 'libraries';
  }

  // Create the path if it does not exist.
  if (!is_dir($path)) {
    drush_op('mkdir', $path);
    drush_log(dt('Directory @path was created', ['@path' => $path]), 'notice');
  }

  // Set the directory to the download location.
  $old_dir = getcwd();
  chdir($path);

  // Download the zip archive.
  if ($file_path = drush_download_file(BXSLIDER_DOWNLOAD_URI)) {
    $filename = basename($file_path);
    $dir_name = BXSLIDER_DOWNLOAD_PREFIX . basename($file_path, '.zip');

    // Remove any existing bxSlider plugin directory.
    if (is_dir($dir_name) || is_dir('bxslider')) {
      drush_delete_dir($dir_name, TRUE);
      drush_delete_dir('bxslider', TRUE);
      drush_log(dt('A existing bxSlider plugin was deleted from @path', ['@path' => $path]), 'notice');
    }

    // Decompress the zip archive.
    drush_tarball_extract($filename);

    // Change the directory name to "bxslider" if needed.
    if ($dir_name != 'bxslider') {
      drush_move_dir($dir_name, 'bxslider', TRUE);
      $dir_name = 'bxslider';
    }
  }

  if (is_dir($dir_name)) {
    drush_log(dt('bxSlider plugin has been installed in @path', ['@path' => $path]), 'success');
  }
  else {
    drush_log(dt('Drush was unable to install the bxSlider plugin to @path', ['@path' => $path]), 'error');
  }

  // Set working directory back to the previous working directory.
  chdir($old_dir);
}
