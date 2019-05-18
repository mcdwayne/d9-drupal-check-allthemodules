<?php

/**
 * @file
 * Drush commands for install_profile_generator.
 */

use Drush\Log\LogLevel;

/**
 * Implements hook_drush_command().
 */
function install_profile_generator_drush_command() {
  $commands = [];

  $commands['install-profile-generate'] = [
    'Description' => 'Generate an installation profile from the current Drupal site',
    'aliases' => ['ipg'],
    'options' => [
      'name' => 'The name of your install profile',
      'machine_name' => 'The machine name of your install profile',
      'description' => 'The description of your install profile',
    ],
  ];

  return $commands;
}

/**
 * Callback for drush install-profile-generate.
 *
 * Checks options given are valid.
 * If only given name or machine name, generate one from the other.
 * Checks user is happy to proceed.
 * Creates file structure for install profile and its config/sync.
 * Creates profile.info.yml
 * Exports config to profile's /config/sync
 * Set config sync folder.
 * Export config to that folder.
 * Change the site to use the new config sync folder.
 */
function drush_install_profile_generator_install_profile_generate() {
  $name = drush_get_option('name', '');
  $machine_name = drush_get_option('machine_name', '');
  $description = drush_get_option('description', '');

  /** @var \Drupal\install_profile_generator\Services\Validator $validator */
  $validator = \Drupal::service('install_profile_generator.validator');

  if ($name && empty($machine_name)) {
    // Generate machine name from name.
    $machine_name = $validator->convertToMachineName($name);
  }

  if ($machine_name && empty($name)) {
    // Generate name from machine name.
    $name = $machine_name;
  }

  try {
    $validator->validate($name, $machine_name);
  }
  catch (\Exception $e) {
    drush_set_error('install_profile_generate_validate', $e->getMessage());
    // Exit with an error status.
    return FALSE;
  }

  if (!install_profile_generator_confirm($machine_name)) {
    // The user has chosen to not continue. There's no error.
    return drush_user_abort();
  }

  /** @var \Drupal\install_profile_generator\Services\ProfileFactory $profile_factory */
  $profile_factory = \Drupal::service('install_profile_generator.profile_factory');
  $profile = $profile_factory->create($machine_name, $name, $description);
  try {
    $profile
      ->create()
      ->writeConfig()
      ->install();
  }
  catch (\Exception $e) {
    drush_set_error('install_profile_generate_profile', $e->getMessage());
    // Exit with an error status.
    return FALSE;
  }

  // We've changed the install profile and which extensions are running. We need
  // to use the hammer.
  drupal_flush_all_caches();

  // Change the site to use the new sync directory if possible.
  $settings_file = \Drupal::service('site.path') . '/settings.php';
  $perms = NULL;
  // Use a relative path for writing to settings.php.
  $profile_path = 'profiles/' . $machine_name;
  // Try and make settings.php writable.
  if (!is_writable($settings_file)) {
    $perms = fileperms($settings_file);
    @chmod($settings_file, 0644);
  }

  if (is_writable($settings_file)) {
    // Include any other config directories in the rewritten settings.php
    // variable.
    global $config_directories;
    $settings = ['config_directories' => []];
    foreach ($config_directories as $key => $config_directory) {
      $settings['config_directories'][$key] = (object) [
        'value' => $config_directory,
        'required' => TRUE,
      ];
    }
    $settings['config_directories'][CONFIG_SYNC_DIRECTORY] = (object) [
      'value' => $profile_path . '/config/sync',
      'required' => TRUE,
    ];
    // Rewrite settings.php, which also sets the value as global variable.
    include_once \Drupal::root() . '/core/includes/install.inc';
    drupal_rewrite_settings($settings);
  }

  // If we couldn't write to settings.php tell the user what to do.
  if (!is_writable($settings_file)) {
    drush_log(dt("Add the following line to $settings_file\n\$config_directories[CONFIG_SYNC_DIRECTORY] = '$profile_path/config/sync';\n"), LogLevel::WARNING);
  }

  // Change the permissions back if we changed them.
  if ($perms) {
    @chmod($settings_file, $perms);
  }

  drush_log(dt('Created new installation profile and exported configuration to it. The "Install Profile Generator" module has been uninstalled. To update the profile with any configuration changes use the "drush config-export" command.'), LogLevel::OK);

  // Test that core can do configuration installs.
  include_once \Drupal::root() . '/core/includes/install.core.inc';
  if (!function_exists('install_config_import_batch')) {
    drush_log(dt('In order to fully benefit from your new install profile you need to apply the latest patch on https://www.drupal.org/node/2788777.'), LogLevel::WARNING);
  }
  return TRUE;
}

/**
 * Confirm with the user whether generation should proceed.
 *
 * @param string $machine_name
 *   Machine name of the new profile.
 *
 * @return bool
 *   TRUE - proceed with generation of install profile.
 *   FALSE - abort generation of install profile.
 */
function install_profile_generator_confirm($machine_name) {
  drush_print(dt('About to generate a new install profile with the machine name "@machine_name"', ['@machine_name' => $machine_name]));
  return drush_confirm(dt('Do it?'));
}
