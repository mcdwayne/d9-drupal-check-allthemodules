<?php

/**
 * @file
 * Drush integration for jcarousel.
 */

/**
 * The jCarousel plugin URI.
 */
define('JCAROUSEL_DOWNLOAD_URI', 'https://github.com/jsor/jcarousel/archive/0.3.8.zip');
define('JCAROUSEL_SWIPE_DOWNLOAD_URIOAD_URI', 'https://github.com/snake-345/jcarouselSwipe/archive/0.3.7.zip');

/**
 * Implements hook_drush_command().
 */
function jcarousel_drush_command() {
  $items = [];

  // The key in the $items array is the name of the command.
  // No bootstrap.
  $items['jcarousel-plugin'] = [
    'callback' => 'drush_jcarousel_lib_plugin',
    'description' => dt('Download and install the jCarousel plugin.'),
    'bootstrap' => DRUSH_BOOTSTRAP_NONE,
    'arguments' => [
      'path' => dt('Optional. A path where to install the jCarousel plugin. If omitted Drush will use the default location.'),
    ],
    'aliases' => ['jcarouselplugin'],
  ];

  $items['jcarousel-swipe-plugin'] = [
    'callback' => 'drush_jcarousel_swipe_lib_plugin',
    'description' => dt('Download and install the jCarousel swipe plugin.'),
    'bootstrap' => DRUSH_BOOTSTRAP_NONE,
    'arguments' => [
      'path' => dt('Optional. A path where to install the jCarousel swipe plugin. If omitted Drush will use the default location.'),
    ],
    'aliases' => ['jcarouselswipeplugin'],
  ];

  return $items;
}

/**
 * Implements hook_drush_help().
 */
function jcarousel_drush_help($section) {
  if ($section == 'drush:jcarousel-plugin') {
    return dt('Download and install the jCarousel plugin from https://github.com/jsor/jcarousel, default location is /libraries.');
  }
  if ($section == 'drush:jcarousel-swipe-plugin') {
    return dt('Download and install the jCarousel plugin from https://github.com/jsor/jcarousel, default location is /libraries.');
  }
}

/**
 * Command to download the jCarousel plugin.
 */
function drush_jcarousel_lib_plugin() {
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
  if ($filepath = drush_download_file(JCAROUSEL_DOWNLOAD_URI)) {
    $filename = basename($filepath);
    $dirname = basename($filepath, '.zip');

    // Remove any existing jCarousel plugin directory.
    if (is_dir($dirname) || is_dir('jcarousel')) {
      drush_delete_dir($dirname, TRUE);
      drush_delete_dir('jcarousel', TRUE);
      drush_log(dt('A existing jCarousel plugin was deleted from @path', ['@path' => $path]), 'notice');
    }

    // Decompress the zip archive.
    drush_tarball_extract($filename, $dirname);

    // Change the directory name to "jCarousel" if needed.
    if ($dirname != 'jcarousel') {
      drush_mkdir('jcarousel');
      chdir($dirname);
      drush_move_dir('jcarousel-0.3.8', '../jcarousel', TRUE);
      chdir('..');
      drush_delete_dir($dirname);
      $dirname = 'jcarousel';
    }
  }

  if (is_dir($dirname)) {
    drush_log(dt('jCarousel plugin has been installed in @path', ['@path' => $path]), 'success');
  }
  else {
    drush_log(dt('Drush was unable to install the jCarousel plugin to @path', ['@path' => $path]), 'error');
  }

  // Set working directory back to the previous working directory.
  chdir($olddir);
}

/**
 * Command to download the jCarousel plugin.
 */
function drush_jcarousel_swipe_lib_plugin() {
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
  if ($filepath = drush_download_file(JCAROUSEL_SWIPE_DOWNLOAD_URIOAD_URI)) {
    $filename = basename($filepath);
    $dirname = basename($filepath, '.zip');

    // Remove any existing jCarousel plugin directory.
    if (is_dir($dirname) || is_dir('jcarouselSwipe')) {
      drush_delete_dir($dirname, TRUE);
      drush_delete_dir('jcarouselSwipe', TRUE);
      drush_log(dt('A existing jCarousel swipe plugin was deleted from @path', ['@path' => $path]), 'notice');
    }

    // Decompress the zip archive.
    drush_tarball_extract($filename, $dirname);

    // Change the directory name to "jCarousel" if needed.
    if ($dirname != 'jcarouselSwipe') {
      drush_mkdir('jcarouselSwipe');
      chdir($dirname);
      drush_move_dir('jcarouselSwipe-0.3.5', '../jcarouselSwipe', TRUE);
      chdir('..');
      drush_delete_dir($dirname);
      $dirname = 'jcarouselSwipe';
    }
  }

  if (is_dir($dirname)) {
    drush_log(dt('jCarousel swipe plugin has been installed in @path', ['@path' => $path]), 'success');
  }
  else {
    drush_log(dt('Drush was unable to install the jCarousel swipe plugin to @path', ['@path' => $path]), 'error');
  }

  // Set working directory back to the previous working directory.
  chdir($olddir);
}
