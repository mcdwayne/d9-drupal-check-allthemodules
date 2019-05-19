<?php

namespace Drupal\tag1quo\Adapter\Settings;

use Drupal\tag1quo\VersionedClass;

/**
 * Class Settings.
 *
 * @internal This class is subject to change.
 */
class Settings extends VersionedClass {

  /**
   * Creates a new Settings instance.
   *
   * @return static
   */
  public static function load() {
    return static::createVersionedStaticInstance();
  }

  /**
   * Returns a setting.
   *
   * Settings can be set in settings.php in the $settings array and requested
   * by this function. Settings should be used over configuration for read-only,
   * possibly low bootstrap configuration that is environment specific.
   *
   * @param string $name
   *   The name of the setting to return.
   * @param mixed $default
   *   (optional) The default value to use if this setting is not set.
   *
   * @return mixed
   *   The value of the setting, the provided default if not set.
   */
  public function get($name, $default = NULL) {
    $value = \variable_get($name);
    return $value !== NULL ? $value : $default;
  }

}
