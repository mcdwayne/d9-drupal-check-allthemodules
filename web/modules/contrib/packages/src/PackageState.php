<?php

namespace Drupal\packages;

/**
 * Class PackageState.
 *
 * Controls the state of a package for a given user.
 *
 * @package Drupal\packages
 */
class PackageState {

  /**
   * The package plugin Id.
   *
   * @var string
   */
  protected $packageId;

  /**
   * The package enabled status.
   *
   * @var bool
   */
  protected $enabled = FALSE;

  /**
   * Whether or not the user has access to this package.
   *
   * @var bool
   */
  protected $access = TRUE;

  /**
   * The package settings.
   *
   * @var array
   */
  protected $settings = [];

  /**
   * Constructor.
   *
   * @param string $package_id
   *   The package Id.
   */
  public function __construct($package_id) {
    $this->packageId = $package_id;
  }

  /**
   * Get the package Id.
   *
   * @return string
   *   The package Id.
   */
  public function getPackageId() {
    return $this->packageId;
  }

  /**
   * Set the package settings.
   *
   * @param array $settings
   *   An array of package settings.
   * @param bool $merge
   *   TRUE if the passed in settings should be merged with the existing settings,
   *   or FALSE if the settings should be replaced. Defaults to FALSE.
   */
  public function setSettings(array $settings, $merge = FALSE) {
    $this->settings = !$merge ? $settings : array_merge($this->settings, $settings);
  }

  /**
   * Set an individual settings.
   *
   * @param mixed $key
   *   The setting array key.
   * @param mixed $value
   *   The setting array value.
   */
  public function setSetting($key, $value) {
    $this->settings[$key] = $value;
  }

  /**
   * Get the settings.
   *
   * @return array
   *   The package settings.
   */
  public function getSettings() {
    return $this->settings;
  }

  /**
   * Get an individual setting.
   *
   * @param mixed $key
   *   The setting key.
   *
   * @return mixed
   *   The setting value, or NULL if it does not exist.
   */
  public function getSetting($key) {
    return isset($this->settings[$key]) ? $this->settings[$key] : NULL;
  }

  /**
   * Check if the user has access to this package.
   *
   * This pertains only to permission checking and does not include the enabled
   * status of the package. To check both, use isActive().
   *
   * @return bool
   *   TRUE if the user has access to this package, otherwise FALSE.
   */
  public function hasAccess() {
    return $this->access;
  }

  /**
   * Set the user's access to this package.
   *
   * @param bool $access
   *   TRUE if the user has access to this package, otherwise FALSE. This should
   *   only pertain to permission and not the enabled status of the package.
   */
  public function setAccess($access) {
    $this->access = $access;
  }

  /**
   * Check if the user has enabled this package.
   *
   * Packages can be enabled without the user actually enabling them. Package
   * plugins have an "enabled" property which means the package is enabled
   * by default; until a user chooses to disable it.
   *
   * This pertains only to the enabled status and doesn't check access. It should
   * not be used when determining if package functionality should be available
   * to the user. To check both, use isActive().
   *
   * @return bool
   *   TRUE if the package is enabled for the user, otherwise FALSE.
   */
  public function isEnabled() {
    return $this->enabled;
  }

  /**
   * Check if the user has disabled this package.
   *
   * @see isEnabled()
   *
   * @return bool
   *   TRUE if the package is disabled for the user, otherwise FALSE.
   */
  public function isDisabled() {
    return !$this->isEnabled();
  }

  /**
   * Set the status of this package to enabled for the user.
   */
  public function enable() {
    $this->enabled = TRUE;
  }

  /**
   * Set the status of this package to disabled for the user.
   */
  public function disable() {
    $this->enabled = FALSE;
  }

  /**
   * Set the enabled status of this package for the user.
   *
   * @param bool $enabled
   *   TRUE if the package is enabled, otherwise FALSE.
   */
  public function setEnabled($enabled) {
    $this->enabled = $enabled;
  }

  /**
   * Determine if this package is considered active.
   *
   * Active means the package is enabled and the user has access to it. This
   * function is the one that should be used when determining if package
   * functionality should be available to the user.
   *
   * @return bool
   *   TRUE if this package is enabled and the user has access, otherwise FALSE.
   */
  public function isActive() {
    return $this->isEnabled() && $this->hasAccess();
  }

}
