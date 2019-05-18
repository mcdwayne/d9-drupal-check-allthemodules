<?php

namespace Drupal\install_profile_generator\Commands;

use Drupal\install_profile_generator\Services\ProfileFactory;
use Drupal\install_profile_generator\Services\Validator;
use Drush\Commands\DrushCommands;

/**
 * Creates a new install profile from the current site.
 */
class InstallProfileGeneratorCommands extends DrushCommands {

  /**
   * Validator service to share code between drush 8 and 9.
   *
   * @var \Drupal\install_profile_generator\Services\Validator
   */
  protected $validator;

  /**
   * Factory to create Profile objects.
   *
   * @var \Drupal\install_profile_generator\Services\ProfileFactory
   */
  protected $profileFactory;

  /**
   * InstallProfileGeneratorCommands constructor.
   *
   * @param \Drupal\install_profile_generator\Services\Validator $validator
   *   Service to validate input.
   * @param \Drupal\install_profile_generator\Services\ProfileFactory $profile_factory
   *   Profile object factory to create and install the profile.
   */
  public function __construct(Validator $validator, ProfileFactory $profile_factory) {
    $this->validator = $validator;
    $this->profileFactory = $profile_factory;
  }

  /**
   * Generate an install profile form the current site.
   *
   * @param array $options
   *   Options passed to command.
   *
   * @command install:profile:generate
   * @option name The name of your install profile
   * @option machine_name The machine name of your install profile
   * @option description The description of your install profile
   * @aliases ipg,install-profile-generate
   *
   * @throws \Exception
   */
  public function profileGenerate(array $options = [
    'name' => NULL,
    'machine_name' => NULL,
    'description' => NULL,
  ]) {
    $name = $options['name'];
    $machine_name = $options['machine_name'];
    $description = $options['description'];

    if ($name && empty($machine_name)) {
      // Generate machine name from name.
      $machine_name = $this->validator->convertToMachineName($name);
    }

    if ($machine_name && empty($name)) {
      // Generate name from machine name.
      $name = $machine_name;
    }

    $this->validator->validate($name, $machine_name);

    if (!$this->io()->confirm(dt('About to generate a new install profile with the machine name "@machine_name". Continue?', ['@machine_name' => $machine_name]))) {
      // The user has chosen to not continue. There's no error.
      // Hmmm no equivalent in Drush 9.
      // return drush_user_abort();
      return;
    }

    // Create the new install profile.
    $profile = $this->profileFactory->create($machine_name, $name, $description);
    $profile
      ->create()
      ->writeConfig()
      ->install();

    // We've changed the install profile and which extensions are running. We
    // need to use the hammer.
    drupal_flush_all_caches();

    // Change the site to use the new sync directory if possible.
    // @todo inject?
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
      $settings['settings']['install_profile'] = (object) [
        'value' => $machine_name,
        'required' => TRUE,
      ];
      // Rewrite settings.php, which also sets the value as global variable.
      include_once \Drupal::root() . '/core/includes/install.inc';
      drupal_rewrite_settings($settings);
    }

    // If we couldn't write to settings.php tell the user what to do.
    if (!is_writable($settings_file)) {
      $this->logger()->warning(dt("Add the following lines to $settings_file\n\$config_directories[CONFIG_SYNC_DIRECTORY] = '$profile_path/config/sync';\n\$settings['install_profile'] = '$machine_name';"));
    }

    // Change the permissions back if we changed them.
    if ($perms) {
      @chmod($settings_file, $perms);
    }

    $this->io()->writeln("\n" . dt('<info>Created new installation profile and exported configuration to it. The "Install Profile Generator" module has been uninstalled. To update the profile with any configuration changes use the "drush config-export" command.</info>'));

    // Test that core can do configuration installs.
    include_once \Drupal::root() . '/core/includes/install.core.inc';
    if (!function_exists('install_config_import_batch')) {
      $this->logger()->warning(dt('In order to fully benefit from your new install profile you need to apply the latest patch on https://www.drupal.org/node/2788777.'));
    }
  }

}
