<?php

/**
 * @file
 * Contains vuejs-download drush command.
 */

const VUEJS_LIBRARY_DIR = 'libraries/vuejs';

/**
 * Implements hook_drush_command().
 */
function vuejs_drush_command() {

  $items['vuejs-download'] = [
    'description' => 'Downloads Vue.js libraries.',
    'arguments' => [
      'library_name' => 'Library name',
    ],
    'required-arguments' => TRUE,
    'bootstrap' => DRUSH_BOOTSTRAP_DRUPAL_FULL,
    'aliases' => ['vue'],
    'examples' => [
      'drush vue-download vue-router' => 'Download vue-router library.',
    ],
  ];

  return $items;
}

/**
 * Callback function for vuejs-download command.
 */
function drush_vuejs_download($library_name) {

  if (!is_dir('libraries')) {
    return drush_set_error('VUEJS', dt('Directory libraries does not exist.'));
  }

  $libraries = Drupal::config('vuejs.settings')->get('libraries');
  $library_machine_name = str_replace('-', '_', $library_name);
  if (!isset($libraries[$library_machine_name])) {
    return drush_set_error('VUEJS', dt('Library @library is not supported.', ['@library' => $library_name]));
  }
  $library = $libraries[$library_machine_name];

  $download_dir = VUEJS_LIBRARY_DIR . '/' . $library_name . '/' . $library['version'];
  if (!is_dir($download_dir)) {
    drush_mkdir($download_dir);
  }

  foreach (['', '.min'] as $suffix) {
    $download_url = sprintf(
      'https://raw.githubusercontent.com/vuejs/%s/%s%s/dist/%s%s.js',
      $library_name,
      // Vue resource does not prefix version tags.
      $library_name == 'vue-resource' ? '' : 'v',
      $library['version'],
      $library_name,
      $suffix
    );

    $result = drush_shell_exec('wget --timeout=15 -O %s %s', $download_dir . '/' . $library_name . $suffix . '.js', $download_url);
    if (!$result) {
      return drush_set_error('VUEJS', dt('Could not download file @file.', ['@file' => $download_url]));
    }
  }

  drush_log('Done!', 'success');
}
