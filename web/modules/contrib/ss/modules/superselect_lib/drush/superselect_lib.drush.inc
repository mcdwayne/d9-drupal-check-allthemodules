<?php

/**
 * @file
 * Drush integration for chosen.
 */

/**
 * The Superselect plugin URI.
 */
define('SUPERSELECT_DOWNLOAD_URI', 'https://github.com/dragonofmercy/Tokenize2/archive/master.zip');

/**
 * Implements hook_drush_command().
 */
function superselect_lib_drush_command() {
  $items = [];

  // The key in the $items array is the name of the command.
  // No bootstrap.
  $items['superselect-plugin'] = [
    'callback' => 'drush_superselect_lib_plugin',
    'description' => dt('Download and install the Superselect plugin.'),
    'bootstrap' => DRUSH_BOOTSTRAP_DRUSH,
    'arguments' => [
      'path' => dt('Optional. A path where to install the Superselect plugin. If omitted Drush will use the default location.'),
    ],
    'aliases' => ['superselectplugin'],
  ];

  return $items;
}

/**
 * Implements hook_drush_help().
 */
function superselect_lib_drush_help($section) {
  switch ($section) {
    case 'drush:superselect-plugin':
      return dt('Download and install the Superselect plugin from https://github.com/dragonofmercy/Tokenize2/, default location is /libraries.');
  }
}

/**
 * Command to download the Chosen plugin.
 */
function drush_superselect_lib_plugin() {
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
  $olddir = getcwd();
  chdir($path);

  // Download the zip archive.
  if ($filepath = drush_download_file(SUPERSELECT_DOWNLOAD_URI)) {
    $filename = basename($filepath);
    $dirname = basename($filepath, '.zip');

    // Remove any existing Superselect plugin directory.
    if (is_dir($dirname) || is_dir('superselect')) {
      drush_delete_dir($dirname, TRUE);
      drush_delete_dir('superselect', TRUE);
      drush_log(dt('A existing Superselect plugin was deleted from @path', ['@path' => $path]), 'notice');
    }

    // Decompress the zip archive.
    drush_tarball_extract($filename, $dirname);

    // Change the directory name to "superselect" if needed.
    if ($dirname != 'superselect') {
      $target = $dirname . '/Tokenize2-master';
      if (is_dir($target)) {
        drush_move_dir($target, 'superselect', TRUE);
        $dirname = 'superselect';
      }
    }
  }

  if (is_dir($dirname)) {
    drush_log(dt('Superselect plugin has been installed in @path', ['@path' => $path]), 'success');
  }
  else {
    drush_log(dt('Drush was unable to install the Superselect plugin to @path', ['@path' => $path]), 'error');
  }
  // Set working directory back to the previous working directory.
  chdir($olddir);
}
