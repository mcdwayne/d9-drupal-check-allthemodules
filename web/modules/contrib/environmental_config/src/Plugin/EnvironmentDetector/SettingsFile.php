<?php

namespace Drupal\environmental_config\Plugin\EnvironmentDetector;

use Drupal\Core\Site\Settings;

/**
 * Provides a 'EnvironmentDetector' plugin.
 *
 * @EnvironmentDetector(
 *   id = "settingsfile",
 *   name = @Translation("Env from settings file"),
 *   description = "Tries to detect the env from a key in the settings file."
 * )
 */
class SettingsFile extends EnvPluginBase {

  /**
   * Gets the environment.
   *
   * @inheritdoc
   */
  public function getEnvironment($arg = NULL) {
    $settings = Settings::get('environmental_config', []);
    return isset($settings['plugins']['settingsfile']['env']) ? $settings['plugins']['settingsfile']['env'] : NULL;
  }

  /**
   * Gets the plugin weight.
   *
   * @inheritdoc
   */
  public function getWeight() {
    return -15;
  }

}
