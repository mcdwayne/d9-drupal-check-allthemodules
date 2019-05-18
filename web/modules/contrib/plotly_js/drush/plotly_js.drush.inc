<?php

/**
 * @file
 * Drush integration for plotly_js.
 */

/**
 * Implements hook_drush_command().
 */
function plotly_js_drush_command() {
  $items = [];

  // Defining a drush command to install the plotly_js external libraries.
  $items['plotly_js'] = [
    'description' => dt('Downloads the Plotly Js library from the remote source and installs it.'),
    'callback' => 'plotly_js_drush_execute',
    'arguments' => [
      'command' => dt('The command being executed on plotly_js.'),
    ],
  ];
  return $items;
}

/**
 * Implements hook_drush_help().
 *
 * Help description for the plotly_js drush commands.
 */
function plotly_js_drush_help($section) {
  switch ($section) {
    case 'drush:plotly_js download':
      return dt("Downloads the latest plotly_js external library.");
  }
}

/**
 * Drush command callback.
 *
 * Installs the external libraries required for plotly_js plugins.
 *
 * @param string $command
 *   The name of the command being executed.
 */
function plotly_js_drush_execute($command) {
  switch ($command) {

    case 'download':
      // Get the library information.
      $plotly_library = \Drupal::service('library.discovery')->getLibraryByName('plotly_js', 'plotly_js.plotly');

      // Create a file system service manager.
      $fileSystem = \Drupal::service('file_system');

      // Remove the existing directory if it exists.
      $library_directory = $fileSystem->realpath('libraries/plotly');
      if (!empty($library_directory)) {
        // Remove the existing file.
        if (file_exists($library_directory . '/plotly-latest.min.js')) {
          $fileSystem->unlink($library_directory . '/plotly-latest.min.js');
        }
        $fileSystem->rmdir($library_directory);
      }

      // Create the directory.
      $fileSystem->mkdir('libraries/plotly');

      // Download the file.
      if (drush_shell_exec('wget ' . $plotly_library['remote'] . ' -O ' . $fileSystem->realpath('libraries/plotly') . '/plotly-latest.min.js')) {
        drush_log(dt('plotly_js library has been successfully installed at libraries/plotly', [], 'success'), 'success');
      }
      else {
        drush_log(dt('Error: unable to install plotly_js library', [], 'error'), 'error');
      }

      break;

    default:
      drush_log(dt('Plotly.js command `@command` not found', [
        '@command' => $command,
      ], 'error'), 'error');
      break;

  }
}

/**
 * Implements drush_MODULE_post_COMMAND().
 *
 * This is used to install library after the module is enabled in drush.
 */
function drush_plotly_js_post_pm_enable() {
  $extensions = func_get_args();
  // Deal with comma delimited extension list.
  if (strpos($extensions[0], ',') !== FALSE) {
    $extensions = explode(',', $extensions[0]);
  }

  if (in_array('plotly_js', $extensions) && !drush_get_option('skip')) {
    if (drush_confirm('Do you want to install external library for use with plotly_js? (Note: you can install manually later)')) {
      plotly_js_drush_execute('download');
    }
  }
}
