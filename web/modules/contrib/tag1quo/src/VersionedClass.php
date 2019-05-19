<?php

namespace Drupal\tag1quo;

/**
 * Class VersionedClass.
 *
 * @internal This class is subject to change.
 */
class VersionedClass {

  /**
   * An array of statically cached object versioned instances.
   *
   * @var static[]
   */
  private static $versionedInstances;

  /**
   * Creates a new versioned instance, if one exists.
   *
   * @param array $args
   *   The args that should be passed to the constructor.
   *
   * @return static
   *
   * @throws \RuntimeException
   *   When a versioned class is explicitly created or when a versioned class
   *   is attempting to be used that is not supported for the currently
   *   installed version of core.
   */
  public static function createVersionedInstance(array $args = array()) {
    $class = get_called_class();

    // Ensure that a versioned class was not explicitly invoked.
    if (is_numeric(substr($class, -1, 1))) {
      throw new \RuntimeException(sprintf('You should not explicitly create a versioned class. The versioned class is created automatically, use %s::create() instead.', substr($class, 0, -1)));
    }

    $version = _tag1quo_drupal_version(TRUE);
    if (($versioned_class = $class . $version) && class_exists($versioned_class) && is_subclass_of($versioned_class, $class)) {
      $class = $versioned_class;
    }
    try {
      $reflection = new \ReflectionClass($class);
      /** @var static $instance */
      $instance = $reflection->newInstanceArgs($args);
      return $instance;
    }
    catch (\Exception $e) {
      return new static();
    }
  }

  /**
   * Creates a new versioned instance, if one exists.
   *
   * @param array $args
   *   The args that should be passed to the constructor.
   *
   * @return static
   *
   * @throws \RuntimeException
   *   When a versioned class is attempting to be used that is not supported
   *   for the currently installed version of core.
   */
  public static function createVersionedStaticInstance(array $args = array()) {
    $class = get_called_class();
    $id = "$class:" . static::hashArray($args);
    if (!isset(self::$versionedInstances[$id])) {
      self::$versionedInstances[$id] = call_user_func_array("$class::createVersionedInstance", array($args));
    }
    return self::$versionedInstances[$id];
  }

  protected static function hashArray(array $array) {
    $serialized = serialize(static::sanitizeArray($array));
    return hash('sha256', $serialized);
  }

  protected static function sanitizeArray($array, $depth = 25) {
    $sanitized = array();
    foreach ($array as $key => $value) {
      if (is_object($value)) {
        $value = $depth ? get_object_vars($value) : get_class($value);
      }
      $sanitized[$key] = is_array($value) ? static::sanitizeArray($value, $depth - 1) : $value;
    }
    return $sanitized;
  }

}
