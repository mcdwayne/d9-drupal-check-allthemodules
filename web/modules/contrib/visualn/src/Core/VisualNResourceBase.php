<?php

namespace Drupal\visualn\Core;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\TypedData\Plugin\DataType\Map;
use Drupal\visualn\Core\VisualNResourceInterface;
use Drupal\visualn\Plugin\EntityInterface;
use Drupal\visualn\Plugin\TypedDataInterface;
use Drupal\visualn\Resource;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\MapDataDefinition;

/**
 * Base class for VisualN Resource plugins.
 *
 * VisualN resources making use of this base class have to implement
 * the static method propertyDefinitions().
 *
 */
abstract class VisualNResourceBase extends Resource implements VisualNResourceInterface {

  // maybe use some abstract class which is based on Resource class and add methods like getResourceDefition() etc.
  //    see FieldItemBase::getFieldDefinition

  /**
   * @todo: add to the interface
   * @todo: or move back to VisualN helper class and add into VisualNResourceBase
   *
   *
   * @see ResourceDataDefinition
   */
  // @todo: move into VisualNResourceBase class
  public static function propertyDefinitions($property_values = []) {
    $properties = [];
    foreach ($property_values as $name => $value) {
      // @todo: using DataDefinition::create() causes an error
      //    when calling Resource::validate() (see VisualN::getResourceByOptions())
      if (is_array($value)) {
        // @todo: what if data is a large array containing much info, won't it take unreasonable processing time here?
        //    maybe just use 'any' data type
        // @todo: DataDefinition can't be used to create "map" data type
        // @see https://www.drupal.org/project/drupal/issues/2874458
        $properties[$name] = MapDataDefinition::create('map');
      }
      elseif (is_string($value)) {
        $properties[$name] = DataDefinition::create('string');
      }
      else {
        $properties[$name] = DataDefinition::create('any');
      }
    }

    return $properties;
  }

  // @todo: maybe override __construct(), see FieldItemBase for example


  /**
   * {@inheritdoc}
   *
   * @todo: description taken from FieldItemBase class
   * Different to the parent Map class, we avoid creating property objects as
   * far as possible in order to optimize performance. Thus we just update
   * $this->values if no property object has been created yet.
   */
  protected function writePropertyValue($property_name, $value) {

    // For defined properties there is either a property object or a plain
    // value that needs to be updated.
    if (isset($this->properties[$property_name])) {
      $this->properties[$property_name]->setValue($value, FALSE);
    }
    else {
      $this->values[$property_name] = $value;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function __get($name) {

    // There is either a property object or a plain value - possibly for a
    // not-defined property. If we have a plain value, directly return it.
    if (isset($this->properties[$name])) {
      return $this->properties[$name]->getValue();
    }
    elseif (isset($this->values[$name])) {
      return $this->values[$name];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function __set($name, $value) {

    // Support setting values via property objects, but take care in as the
    // value of the 'entity' property is typed data also.
    if ($value instanceof TypedDataInterface && !$value instanceof EntityInterface) {
      $value = $value->getValue();
    }
    $this->set($name, $value);
  }

  /**
   * {@inheritdoc}
   */
  public function __isset($name) {
    if (isset($this->properties[$name])) {
      return $this->properties[$name]->getValue() !== NULL;
    }
    return isset($this->values[$name]);
  }

  /**
   * {@inheritdoc}
   */
  public function __unset($name) {
    if ($this->definition->getPropertyDefinition($name)) {
      $this->set($name, NULL);
    }
    else {

      // Explicitly unset the property in $this->values if a non-defined
      // property is unset, such that its key is removed from $this->values.
      unset($this->values[$name]);
    }
  }

}
