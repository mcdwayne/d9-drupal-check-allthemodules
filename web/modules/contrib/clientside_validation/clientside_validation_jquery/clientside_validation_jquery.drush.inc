<?php

/**
 * @file
 * Clientside Validation jQuery module drush commands.
 */

use Drupal\Core\Serialization\Yaml;

/**
 * Implements hook_drush_command().
 */
function clientside_validation_jquery_drush_command() {
  $items = [];

  $items['clientside-validation-jquery-libraries-status'] = [
    'description' => 'Show the status of library required by clientside validation jQuery module.',
    'core' => ['8+'],
    'bootstrap' => DRUSH_BOOTSTRAP_DRUPAL_ROOT,
    'examples' => [
      'cvjls' => 'Show the status of library required by clientside validation jQuery module.',
    ],
    'aliases' => ['cvjls'],
  ];

  $items['clientside-validation-jquery-libraries-download'] = [
    'description' => 'Download third party libraries required by clientside validation jQuery module.',
    'core' => ['8+'],
    'bootstrap' => DRUSH_BOOTSTRAP_DRUPAL_ROOT,
    'examples' => [
      'cvjld' => 'Download third party libraries required by the clientside validation jQuery module.',
    ],
    'aliases' => ['cvjld'],
  ];

  $items['clientside-validation-jquery-libraries-remove'] = [
    'description' => 'Remove third party libraries required by clientside validation jQuery module.',
    'core' => ['8+'],
    'bootstrap' => DRUSH_BOOTSTRAP_DRUPAL_ROOT,
    'examples' => [
      'cvjlr' => 'Remove third party libraries required by the clientside validation jQuery module.',
    ],
    'aliases' => ['cvjlr'],
  ];

  return $items;
}

/**
 * Implements drush_hook_COMMAND().
 */
function drush_clientside_validation_jquery_libraries_status() {
  $installed = file_exists(DRUPAL_ROOT . '/libraries/jqueryvalidate/dist/jquery.validate.js');
  $message = $installed ? 'installed' : 'not installed';
  drush_print(dt('Library required for clientside validation jQuery is @message', [
    '@message' => $message,
  ]));
}

/**
 * Implements drush_hook_COMMAND().
 */
function drush_clientside_validation_jquery_libraries_download() {
  // Remove all existing libraries.
  if (drush_clientside_validation_jquery_libraries_remove(FALSE)) {
    drush_print(dt('Removing existing libraries...'));
  }

  // Create the makefile.
  $makefile = file_directory_temp() . '/clientside_validation_jquery.libraries.make.yml';
  file_put_contents($makefile, _drush_clientside_validation_jquery_libraries_make());

  // Download libraries using `drush make`.
  $commandline_args = [$makefile, DRUPAL_ROOT];
  $commandline_options = ['no-core' => 'no-core'];
  drush_invoke_process('@self', 'make', $commandline_args, $commandline_options);

  drupal_flush_all_caches();

  // Remove the makefile.
  file_unmanaged_delete($makefile);
}

/**
 * Implements drush_hook_COMMAND().
 */
function drush_clientside_validation_jquery_libraries_remove($status = TRUE) {
  if ($status) {
    drush_print(dt('Beginning to remove libraries...'));
  }
  $removed = FALSE;

  $library_exists = (file_exists(DRUPAL_ROOT . '/libraries/jqueryvalidate')) ? TRUE : FALSE;
  if ($library_exists) {
    drush_print('jQuery validtion library removed');
    drush_delete_dir(DRUPAL_ROOT . '/libraries/jqueryvalidate', TRUE);
    drupal_flush_all_caches();
    $removed = TRUE;
  }

  return $removed;
}

/**
 * Generate drush make as YAML.
 *
 * @return string
 *   Drush make as YAML
 */
function _drush_clientside_validation_jquery_libraries_make() {
  $data = [
    'core' => '8.x',
    'api' => 2,
    'libraries' => [],
  ];

  $url = 'https://github.com/jquery-validation/jquery-validation/archive/1.17.0.zip';
  $data['libraries']['jqueryvalidate'] = [
    'directory_name' => 'jqueryvalidate',
    'destination' => 'libraries',
    'download' => [
      'type' => 'get',
      'url' => $url,
    ],
  ];

  return Yaml::encode($data);
}
