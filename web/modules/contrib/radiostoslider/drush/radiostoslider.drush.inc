<?php

/**
 * @file
 * Drush integration for the Radios to Slider module.
 */

/**
 * The Radios to Slider plugin URI.
 */
define(
  'RADIOSTOSLIDER_DOWNLOAD_URI',
  'https://github.com/rubentd/radios-to-slider/archive/0.2.2.zip'
);
define('RADIOSTOSLIDER_DOWNLOAD_PREFIX', 'radios-to-slider-');

/**
 * Implements hook_drush_command().
 */
function radiostoslider_drush_command() {
  $items = [];

  // The key in the $items array is the name of the command.
  $items['radiostoslider-plugin'] = [
    'callback' => 'drush_radiostoslider_plugin',
    'description' => dt('Download and install the Radios to Slider plugin.'),
    // No bootstrap.
    'bootstrap' => DRUSH_BOOTSTRAP_DRUSH,
    'arguments' => [
      'path' => dt(
        'Optional. A path where to install the Radios to Slider plugin. If
        omitted Drush will use the default location.'
      ),
    ],
    'aliases' => ['rtsp'],
  ];

  return $items;
}

/**
 * Implements hook_drush_help().
 *
 * This function is called whenever a drush user calls
 * 'drush help <name-of-your-command>'
 */
function radiostoslider_drush_help($section) {
  switch ($section) {
    case 'drush:radiostoslider-plugin':
      return dt('Download and install the Radios to Slider plugin.');
  }
}

/**
 * Command to download the Radios to Slider plugin.
 */
function drush_radiostoslider_plugin() {
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
  if ($filepath = drush_download_file(RADIOSTOSLIDER_DOWNLOAD_URI)) {
    $filename = basename($filepath);
    $dirname = RADIOSTOSLIDER_DOWNLOAD_PREFIX . basename($filepath, '.zip');

    // Remove any existing Radios to Slider plugin directory.
    if (is_dir($dirname) || is_dir('radios-to-slider')) {
      drush_delete_dir($dirname, TRUE);
      drush_delete_dir('radios-to-slider', TRUE);
      drush_log(
        dt(
          'A existing Radios to Slider plugin was deleted from @path',
          ['@path' => $path]
        ),
        'notice'
      );
    }

    // Decompress the zip archive.
    drush_tarball_extract($filename);

    // Change the directory name to "radios-to-slider" if needed.
    if ($dirname != 'radios-to-slider') {
      drush_move_dir($dirname, 'radios-to-slider', TRUE);
      $dirname = 'radios-to-slider';
    }
  }

  if (is_dir($dirname)) {
    drush_log(
      dt(
        'Radios to Slider plugin has been installed in @path',
        ['@path' => $path]
      ),
      'success'
    );
  }
  else {
    drush_log(
      dt(
        'Drush was unable to install the Radios to Slider plugin to @path',
        ['@path' => $path]
        ),
      'error'
    );
  }

  // Set working directory back to the previous working directory.
  chdir($olddir);
}
