<?php
/**
 * @file
 *   Drush integration for the jssor module.
 */

use Symfony\Component\Yaml\Yaml;

/**
 * Implementation of hook_drush_help().
 */
function jssor_drush_help($section) {
  switch ($section) {
    case 'drush:jssor-download':
      return dt('Download and install the jssor library from http://www.jssor.com/, default install location is /libraries.');
  }
}

/**
 * Implements hook_drush_command().
 */
function jssor_drush_command() {
  $file = preg_replace('/(inc|php)$/', 'yml', __FILE__);
  $config = Yaml::parse(file_get_contents($file));
  $items = $config['commands'];
  return $items;
}

/**
 * Implements drush_hook_COMMAND().
 */
function drush_jssor_download() {
  $args = func_get_args();
  if (!empty($args[0])) {
    $path = $args[0];
  }
  else {
    $path = 'libraries';
  }
  $folder = '/jssor-slider';

  // Create the path if it does not exist.
  if (!is_dir($path)) {
    drush_op('mkdir', $path);
    drush_log(dt('Directory @path was created', ['@path' => $path]), 'notice');
  }

  // Check if the folder already exists.
  if (is_dir($path . $folder)) {
    return drush_log('The jssor library appears to be already installed.', 'ok');
  }

  // Set the directory to the download location.
  $olddir = getcwd();
  chdir($path);

  // Download the zip archive
  if ($filepath = drush_download_file('https://github.com/jssor/slider/archive/master.zip')) {
    $filename = basename($filepath);
    $dirname =  strtolower(basename($filepath, '.zip'));

    // Decompress the zip archive
    drush_tarball_extract($filename);

    if (!file_exists($dirname)) {
      $dirname = 'slider-master';
    }

    // Change the directory name to "jssor-slider" if needed.
    if ($dirname != $folder) {
      drush_move_dir($dirname, $folder, TRUE);
      $dirname = $folder;
    }
  }

  if (is_dir($dirname)) {
    drush_log(dt('jssor library has been installed in @path', [
      '@path' => $path]), 'success');
  }
  else {
    drush_log(dt('Drush was unable to install the jssor library to @path', [
      '@path' => $path]), 'error');
  }

  // Set working directory back to the previous working directory.
  chdir($olddir);
}

/**
 * Implementation of drush_hook_pre_pm_enable().
 */
function drush_jssor_pre_pm_enable() {
  $modules = drush_get_context('PM_ENABLE_MODULES');
  if (in_array('jssor', $modules) && !drush_get_option('skip')) {
     drush_jssor_dl_jssor();
  }
}
