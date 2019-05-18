<?php

/**
 * @file
 * Drush integration for shariff.
 */

/**
 * The shariff library plugin URI.
 */
define('SHARIFF_DOWNLOAD_URI', 'https://github.com/heiseonline/shariff/archive/master.zip');
define('SHARIFF_DOWNLOAD_PREFIX', 'shariff-');

/**
 * Implements hook_drush_command().
 */
function shariff_drush_command() {
  $items = [];

  $items['shariff-library'] = [
    'callback' => 'drush_shariff_library',
    'description' => dt('Download/install the shariff library from heiseonline/shariff GitHub repository.'),
    'bootstrap' => DRUSH_BOOTSTRAP_NONE,
    'arguments' => [
      'path' => dt('Optional. A path where to install the shariff library. If omitted Drush will use the default location.'),
    ],
    'aliases' => ['shariff-lib'],
  ];

  return $items;
}

/**
 * Implements hook_drush_help().
 *
 * This function is called whenever a drush user calls
 * 'drush help <name-of-your-command>'
 */
function shariff_drush_help($section) {
  switch ($section) {
    case 'drush:shariff-library':
      return dt('Download/install the shariff library from heiseonline/shariff GitHub repository, default location is the libraries directory.');
  }
}

/**
 * Command to download the shariff library.
 */
function drush_shariff_library() {

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
  if ($filepath = drush_download_file(SHARIFF_DOWNLOAD_URI)) {

    $filename = basename($filepath);
    $dirname = SHARIFF_DOWNLOAD_PREFIX . basename($filepath, '.zip');

    // Remove any existing shariff library directory.
    if (is_dir($dirname) || is_dir('shariff')) {
      drush_delete_dir($dirname, TRUE);
      drush_delete_dir('shariff', TRUE);
      drush_log(dt('A existing shariff library was deleted from @path', ['@path' => $path]), 'notice');
    }

    // Decompress the zip archive.
    drush_tarball_extract($filename);

    // Change the directory name to "shariff" if needed.
    if ($dirname != 'shariff') {
      drush_move_dir('shariff-master', 'shariff', TRUE);
      $dirname = 'shariff';
    }
  }

  if (is_dir($dirname)) {
    drush_log(dt('The shariff library has been successfully downloaded to @path', ['@path' => $path]), 'success');
  }
  else {
    drush_log(dt('Drush was unable to download the shariff library to @path', ['@path' => $path]), 'error');
  }

  // Set working directory back to the previous working directory.
  chdir($olddir);
}
