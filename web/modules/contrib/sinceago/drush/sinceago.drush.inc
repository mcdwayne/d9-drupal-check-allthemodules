<?php

/**
 * @file
 * Drush integration for sinceago.
 */

/**
 * The Sinceago plugin URI.
 */
define('SINCEAGO_DOWNLOAD_URI', 'https://timeago.yarp.com/jquery.timeago.js');
define('SINCEAGO_DOWNLOAD_PREFIX', 'sinceago-');
define('SINCEAGO_FILEPATH', 'sinceago');

/**
 * Implements hook_drush_command().
 */
function sinceago_drush_command() {
  $items = [];

  // The key in the $items array is the name of the command.
  $items['sinceago-plugin'] = [
    'callback' => 'drush_sinceago_plugin',
    'description' => dt('Download and install the Sinceago plugin.'),
     // No bootstrap.
    'bootstrap' => DRUSH_BOOTSTRAP_DRUSH,
    'arguments' => [
      'path' => dt('Optional. A path where to install the Sinceago plugin. If omitted Drush will use the default location.'),
    ],
    'aliases' => ['sinceagoplugin'],
  ];

  return $items;
}

/**
 * Implements hook_drush_help().
 *
 * This function is called whenever a drush user calls
 * 'drush help <name-of-your-command>'
 */
function sinceago_drush_help($section) {
  switch ($section) {
    case 'drush:sinceago-plugin':
      return dt('Download and install the Sinceago plugin from http://timeago.yarp.com/, default location is the libraries directory.');
  }
}

/**
 * Command to download the Colorbox plugin.
 */
function drush_sinceago_plugin() {
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
  if ($filepath = drush_download_file(SINCEAGO_DOWNLOAD_URI)) {

    basename($filepath);
    $handle = @fopen(SINCEAGO_DOWNLOAD_URI, 'r');
    if ($handle) {
      $dirname = 'sinceago';

      // Remove any existing sinceago plugin directory.
      if (is_dir($dirname) || is_dir('sinceago')) {
        unlink($dirname, TRUE);
        unlink('sinceago', TRUE);
        drush_log(dt('A existing Sinceago plugin was deleted from @path', ['@path' => $path]), 'notice');
      }
      drush_op('mkdir', SINCEAGO_FILEPATH, 0777, TRUE);

      copy(SINCEAGO_DOWNLOAD_URI, SINCEAGO_FILEPATH . '/jquery.timeago.js');
    }

    // Change the directory name to "sinceago" if needed.
    if ($dirname != 'sinceago') {
      drush_move_dir($dirname, 'sinceago', TRUE);
      $dirname = 'sinceago';
    }
  }

  if (is_dir($dirname)) {
    drush_log(dt('sinceago plugin has been installed in @path', ['@path' => $path]), 'success');
  }
  else {
    drush_log(dt('Drush was unable to install the sinceago plugin to @path', ['@path' => $path]), 'error');
  }

  // Set working directory back to the previous working directory.
  chdir($olddir);
}
