<?php

namespace Drupal\clever_reach\Component\Infrastructure;

use Drupal;
use CleverReach\Infrastructure\Interfaces\Required\ConfigRepositoryInterface;
use Drupal\clever_reach\Exception\ModuleNotInstalledException;

/**
 * Configuration service.
 *
 * @see \CleverReach\Infrastructure\Interfaces\Required\Configuration
 */
class ConfigRepository implements ConfigRepositoryInterface {

  /**
   * Sets key-value pair to Drupal configuration table.
   *
   * @param string $key
   *   Identifier to store value in configuration.
   * @param mixed $value
   *   Value to associate with identifier.
   *
   * @throws ModuleNotInstalledException
   */
  public function set($key, $value) {
    if (!$this->get('CLEVERREACH_INSTALLED')) {
      // Delete all configuration when module is disabled by uninstall script.
      Drupal::configFactory()->getEditable('clever_reach.settings')->delete();
      // Kill all processes as soon as module uninstall is detected.
      throw new ModuleNotInstalledException('CleverReach module is not currently installed, abort all processes.');
    }

    Drupal::configFactory()->getEditable('clever_reach.settings')->set(strtolower($key), $value)->save();
  }

  /**
   * Gets configuration by key from Drupal configuration table.
   *
   * @param string $key
   *   Identifier to store value in configuration.
   *
   * @return mixed|null
   *   Configuration value.
   */
  public function get($key) {
    $value = Drupal::config('clever_reach.settings')->get(strtolower($key));
    if (!empty($value)) {
      return $value;
    }

    return Drupal::config('clever_reach.settings')->get(
      strtolower(substr($key, strlen('CLEVERREACH_')))
    );
  }

}
