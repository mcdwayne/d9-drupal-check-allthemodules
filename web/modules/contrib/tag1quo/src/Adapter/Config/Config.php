<?php

namespace Drupal\tag1quo\Adapter\Config;

use Drupal\tag1quo\VersionedClass;

/**
 * Class Config.
 *
 * @internal This class is subject to change.
 */
abstract class Config extends VersionedClass {

  /**
   * The raw config name.
   *
   * @var string
   */
  protected $name;

  /**
   * Config constructor.
   *
   * @param string $name
   *   The name of the configuration object.
   */
  public function __construct($name) {
    $this->name = $name;
  }

  /**
   * {@inheritdoc}
   */
  public static function load($name) {
    return parent::createVersionedStaticInstance([$name]);
  }

  /**
   * Converts a config key into what is appropriate for the core version.
   *
   * @param string $key
   *   The name of the config key to convert.
   *
   * @return string
   *   The converted key.
   */
  protected function convertKey($key) {
    return $key;
  }

  /**
   * Removes a config entry.
   *
   * @param string $key
   *   The name of the config key to remove.
   *
   * @return static
   */
  abstract public function clear($key);

  /**
   * Removes all keys from the config object.
   *
   * Note: This does nothing in older versions of Drupal (before config).
   *
   * @return static
   */
  abstract public function delete();

  /**
   * Retrieves a value from config.
   *
   * @param string $key
   *   The config key to retrieve.
   * @param mixed $default
   *   The default value to use if $key is not set.
   *
   * @return mixed
   *   The stored value.
   */
  abstract public function get($key, $default = NULL);

  /**
   * Migrates keys (in the same config).
   *
   * @param array $values
   *   An associative array where the keys are the config "from" keys and the
   *   values are the config "to" keys.
   * @param bool $ignore_null
   *   Flag indicating whether to ignore NULL values, defaults to TRUE.
   */
  public function migrate(array $values = array(), $ignore_null = TRUE) {
    $save = FALSE;
    foreach ($values as $from => $to) {
      // Retrieve the existing config value.
      $value = $this->get($from);

      // Remove the existing config.
      $this->clear($from);

      // Skip config that shouldn't be migrated.
      if ($ignore_null && ($to === NULL || $value === NULL)) {
        continue;
      }

      // Set the new config value.
      $this->set($to, $value);
      $save = TRUE;
    }

    // Finally, save the entire config object.
    if ($save) {
      $this->save();
    }
  }

  /**
   * Sets a value in config.
   *
   * @param string $key
   *   The config key to set.
   * @param mixed $value
   *   The value to set.
   *
   * @return static
   */
  abstract public function set($key, $value);

  /**
   * Saves the config.
   *
   * Note: This does nothing in older versions of Drupal (before config).
   *
   * @return static
   */
  abstract public function save();

}
