<?php

namespace Drupal\entity_counter;

/**
 * Base class for enumerations.
 */
abstract class AbstractEnum {

  /**
   * Static cache of available values, shared with all subclasses.
   *
   * @var array
   */
  protected static $values = [];

  /**
   * Gets all available values.
   *
   * @return array
   *   The available values, keyed by constant.
   *
   * @throws \ReflectionException
   */
  public static function getAll() {
    $class = get_called_class();
    if (!isset(static::$values[$class])) {
      $reflection = new \ReflectionClass($class);
      static::$values[$class] = $reflection->getConstants();
    }

    return static::$values[$class];
  }

  /**
   * Checks whether the provided value is defined.
   *
   * @param string $value
   *   The value.
   *
   * @return bool
   *   True if the value is defined, false otherwise.
   *
   * @throws \ReflectionException
   */
  public static function exists($value) {
    return in_array($value, static::getAll(), TRUE);
  }

  /**
   * Gets the key of the provided value.
   *
   * @param string $value
   *   The value.
   *
   * @return string|false
   *   The key if found, false otherwise.
   *
   * @throws \ReflectionException
   */
  public static function getKey($value) {
    return array_search($value, static::getAll(), TRUE);
  }

}
