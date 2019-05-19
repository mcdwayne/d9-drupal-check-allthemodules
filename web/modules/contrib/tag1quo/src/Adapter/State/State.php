<?php

namespace Drupal\tag1quo\Adapter\State;

use Drupal\tag1quo\VersionedClass;

/**
 * Class State.
 *
 * @internal This class is subject to change.
 */
class State extends VersionedClass {

  /**
   * Creates a new State instance.
   *
   * @return static
   */
  public static function load() {
    return static::createVersionedStaticInstance();
  }

  /**
   * Deletes an item.
   *
   * @param string $key
   *   The item name to delete.
   *
   * @return static
   */
  public function delete($key) {
    \variable_del($key);
    return $this;
  }

  /**
   * Deletes multiple items.
   *
   * @param array $keys
   *   A list of item names to delete.
   *
   * @return static
   */
  public function deleteMultiple(array $keys) {
    foreach ($keys as $key) {
      \variable_del($key);
    }
    return $this;
  }

  /**
   * Returns the stored value for a given key.
   *
   * @param string $key
   *   The key of the data to retrieve.
   * @param mixed $default
   *   The default value to use if the key is not found.
   *
   * @return mixed
   *   The stored value, or NULL if no value exists.
   */
  public function get($key, $default = NULL) {
    $value = \variable_get($key);
    return $value !== NULL ? $value : $default;
  }

  /**
   * Returns the stored key/value pairs for a given set of keys.
   *
   * @param array $keys
   *   A list of keys to retrieve.
   *
   * @return array
   *   An associative array of items successfully returned, indexed by key.
   */
  public function getMultiple(array $keys) {
    $values = array();
    foreach ($keys as $key) {
      $values[$key] = $this->get($key);
    }
    return $values;
  }

  /**
   * Migrates states.
   *
   * @param array $values
   *   An associative array where the keys are the state "from" keys and the
   *   values are the state "to" keys.
   * @param bool $ignore_null
   *   Flag indicating whether to ignore NULL values, defaults to TRUE.
   */
  public function migrate(array $values = array(), $ignore_null = TRUE) {
    foreach ($values as $from => $to) {
      // Retrieve the existing config value.
      $value = $this->get($from);

      // Remove the existing config.
      $this->delete($from);

      // Skip config that shouldn't be migrated.
      if ($ignore_null && ($to === NULL || $value === NULL)) {
        continue;
      }

      // Set the new config value.
      $this->set($to, $value);
    }
  }

  /**
   * Saves a value for a given key.
   *
   * @param string $key
   *   The key of the data to store.
   * @param mixed $value
   *   The data to store.
   *
   * @return static
   */
  public function set($key, $value) {
    \variable_set($key, $value);
    return $this;
  }

  /**
   * Saves key/value pairs.
   *
   * @param array $data
   *   An associative array of key/value pairs.
   *
   * @return static
   */
  public function setMultiple(array $data) {
    foreach ($data as $key => $value) {
      $this->set($key, $value);
    }
    return $this;
  }

}
