<?php

namespace Drupal\visualn\Core;

use Drupal\visualn\ResourceInterface;

/**
 * Defines an interface for VisualN Resource plugins.
 */
interface VisualNResourceInterface extends ResourceInterface {
  // @todo: this should implement some type of general-purpose VisualN Resource interface base
  //    which not necessarily should relate somehow to VisualN Resource plugins


  // @todo: see BooleanItem::propertyDefinitions
  // public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
  // @todo: maybe pass values array and method argument


  /**
   * Defines resource properties.
   *
   * Properties that are required to constitute a valid, non-empty item should
   * be denoted with \Drupal\Core\TypedData\DataDefinition::setRequired().
   *
   * @return \Drupal\Core\TypedData\DataDefinitionInterface[]
   *   An array of property definitions of contained properties, keyed by
   *   property name.
   *
   * @see \Drupal\Core\Field\BaseFieldDefinition
   */
  public static function propertyDefinitions($property_values = []);

  /**
   * Magic method: Gets a property value.
   *
   * @param string $property_name
   *   The name of the property to get; e.g., 'title' or 'name'.
   *
   * @return mixed
   *   The property value.
   *
   * @throws \InvalidArgumentException
   *   If a not existing property is accessed.
   */
  public function __get($property_name);

  /**
   * Magic method: Sets a property value.
   *
   * @param string $property_name
   *   The name of the property to set; e.g., 'title' or 'name'.
   * @param mixed $value
   *   The value to set, or NULL to unset the property. Optionally, a typed
   *   data object implementing Drupal\Core\TypedData\TypedDataInterface may be
   *   passed instead of a plain value.
   *
   * @throws \InvalidArgumentException
   *   If a not existing property is set.
   */
  public function __set($property_name, $value);

  /**
   * Magic method: Determines whether a property is set.
   *
   * @param string $property_name
   *   The name of the property to get; e.g., 'title' or 'name'.
   *
   * @return bool
   *   Returns TRUE if the property exists and is set, FALSE otherwise.
   */
  public function __isset($property_name);

  /**
   * Magic method: Unsets a property.
   *
   * @param string $property_name
   *   The name of the property to get; e.g., 'title' or 'name'.
   */
  public function __unset($property_name);

}
