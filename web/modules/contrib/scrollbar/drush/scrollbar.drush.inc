<?php

/**
 * @file
 * Drush integration for scrollbar.
 */

/**
 * The scrollbar plugin URI.
 */
define('SCROLLBAR_DOWNLOAD_URI', 'https://github.com/theodorosploumis/jscrollpane/archive/2.x.zip');
define('SCROLLBAR_DOWNLOAD_PREFIX', 'jscrollpane-');

/**
 * Implements hook_drush_command().
 */
function scrollbar_drush_command() {
  $items = [];
  
  // The key in the $items array is the name of the command.
  $items['scrollbar-plugin'] = [
    'callback' => 'drush_scrollbar_plugin',
    'description' => dt('Download and install the jScrollPane plugin.'),
    // No bootstrap.
    'bootstrap' => DRUSH_BOOTSTRAP_DRUSH,
    'arguments' => [
      'path' => dt('Optional. A path where to install the jScrollPane plugin. If omitted Drush will use the default location.'),
    ],
    'aliases' => ['scrollbarplugin'],
  ];
  
  return $items;
}

/**
 * Implements hook_drush_help().
 *
 * This function is called whenever a drush user calls
 * 'drush help <name-of-your-command>'
 */
function scrollbar_drush_help($section) {
  switch ($section) {
    case 'drush:scrollbar-plugin':
      return dt('Download and install the jscrollpane plugin from https://github.com/theodorosploumis/jscrollpane, default location is the libraries directory.');
  }
}

/**
 * Command to download the jScrollPane plugin.
 */
function drush_scrollbar_plugin() {
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
  if ($filepath = drush_download_file(SCROLLBAR_DOWNLOAD_URI)) {
    $filename = basename($filepath);
    $dirname = SCROLLBAR_DOWNLOAD_PREFIX . basename($filepath, '.zip');
    
    // Remove any existing jscrollpane plugin directory.
    if (is_dir($dirname) || is_dir('jscrollpane')) {
      drush_delete_dir($dirname, TRUE);
      drush_delete_dir('jscrollpane', TRUE);
      drush_log(dt('A existing jscrollpane plugin was deleted from @path', ['@path' => $path]), 'notice');
    }
    
    // Decompress the zip archive.
    drush_tarball_extract($filename);
    
    // Change the directory name to "jscrollpane" if needed.
    if ($dirname != 'jscrollpane') {
      drush_move_dir($dirname, 'jscrollpane', TRUE);
      $dirname = 'jscrollpane';
    }
  }
  
  if (is_dir($dirname)) {
    drush_log(dt('jscrollpane plugin has been installed in @path', ['@path' => $path]), 'success');
  }
  else {
    drush_log(dt('Drush was unable to install the jscrollpane plugin to @path', ['@path' => $path]), 'error');
  }
  
  // Set working directory back to the previous working directory.
  chdir($olddir);
}
