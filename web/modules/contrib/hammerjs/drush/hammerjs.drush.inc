<?php

/**
 * @file
 * Drush integration for hammerjs.
 */

/**
 * The HammerJS plugin URI.
 */
define('HAMMERJS_DOWNLOAD_URI', 'http://hammerjs.github.io/dist/hammer.min.js');

/**
 * Implements hook_drush_command().
 */
function hammerjs_drush_command() {
  $items = array();

  $items['hammerjs-plugin'] = array(
    'callback' => '_hammerjs_download_lib',
    'description' => dt('Download and install the HammerJS plugin.'),
    'arguments' => array(
      'path' => dt('Optional. A path where to install the HammerJS plugin. If omitted Drush will use the default location.'),
    ),
    'aliases' => array('hammerjsplugin'),
  );

  return $items;
}

/**
 * Implements hook_drush_help().
 */
function hammerjs_drush_help($section) {
  switch ($section) {
    case 'drush:hammerjs-plugin':
      return dt('Download and install the HammerJS plugin from http://hammerjs.github.io, default location is /libraries/hammer.js.');
  }
}

/**
 * Helper function to download the hammerjs plugin.
 */
function _hammerjs_download_lib() {
  $args = func_get_args();
  if (!empty($args[0])) {
    $path = $args[0];
  }
  else {
    $path = 'libraries/hammer.js';
  }

  // Create the path if it does not exist.
  if (!is_dir($path)) {
    drush_op('mkdir', $path);
    drush_log(dt('Directory @path was created', array('@path' => $path)), 'notice');
  }

  // Set the directory to the download location.
  $olddir = getcwd();
  chdir($path);

  // Download the zip archive.
  if (drush_download_file(HAMMERJS_DOWNLOAD_URI, 'hammer.min.js')) {
    drush_log(dt('HammerJS has been installed in @path', array('@path' => $path)), 'success');
  }
  else {
    drush_log(dt('Drush was unable to install the HammerJS to @path', array('@path' => $path)), 'error');
  }

  // Set working directory back to the previous working directory.
  chdir($olddir);
}
