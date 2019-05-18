<?php

namespace Drupal\apitools;

use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;

trait ExtensibleObjectTrait {

  /**
   * Array of loaded values.
   *
   * @var array
   */
  protected $values = [];

  /**
   * Magic method: Gets a property value.
   *
   * @param string $property_name
   *   The name of the property to get; e.g., 'title' or 'name'.
   *
   * @return mixed
   *   The property value.
   */
  public function __get($name) {
    if (isset($this->values[$name])) {
      return $this->values[$name];
    }
    if ($method = $this->getPropertyMethod('get', $name)) {
      return $this->{$method}();
    }
    return NULL;
  }

  /**
   * Magic method: Sets a property value.
   *
   * @param string $property_name
   *   The name of the property to set; e.g., 'title' or 'name'.
   * @param mixed $value
   *   The value to set, or NULL to unset the property. Optionally, a typed
   *   data object implementing Drupal\Core\TypedData\TypedDataInterface may be
   *   passed instead of a plain value.
   */
  public function __set($name, $value) {
    if ($method = $this->getPropertyMethod('set', $name)) {
      return $this->{$method}($value);
    }
    return $this->setValue($name, $value);
  }

  /**
   * Main public method to set value.
   *
   * @param $name
   *   Object property name.
   * @param $value
   *   Mixed property value.
   * @return mixed
   */
  public function set($name, $value) {
    return $this->__set($name, $value);
  }

  /**
   * Main public method to get a value.
   *
   * @param $name
   *   Object property name.
   * @return mixed
   */
  public function get($name) {
    return $this->__get($name);
  }

  public function add($name, $value) {
    $values = &$this->values['_add'][$name];
    if (!$values) {
      $values = [];
    }
    if ($method = $this->getPropertyMethod('add', $name)) {
      $value = $this->{$method}($value);
    }
    $values[] = $value;
    return $value;
  }

  /**
   * Helper method to populate values array.
   *
   * @param $name
   *   Object property name.
   * @param $value
   *   Mixed property value.
   * @return array
   */
  protected function setValue($name, $value) {
    $this->values[$name] = $value;
    return $this->values[$name];
  }

  /**
   * Convert property string to method name if it exists for the current object.
   *
   * @param $type
   *   Method type, either get or set.
   * @param $name
   *   Requested property name.
   * @return bool|string
   */
  protected function getPropertyMethod($type, $name) {
    $converter = new CamelCaseToSnakeCaseNameConverter(NULL, FALSE);
    $method = $type . $converter->denormalize($name);
    return method_exists($this, $method) ? $method : FALSE;
  }
}
