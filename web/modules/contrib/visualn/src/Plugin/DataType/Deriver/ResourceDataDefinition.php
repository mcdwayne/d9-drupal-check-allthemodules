<?php

namespace Drupal\visualn\Plugin\DataType\Deriver;

//use Drupal\Core\TypedData\ComplexDataDefinitionInterface;
//use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\MapDataDefinition;


/**
 * @todo: review the code
 *
 * For now ResourceDataDefinition extends MapDataDefinition though maybe should
 * extend DataDefinition class and implement ComplexDataDefinitionInterface methods
 * as FieldItemDataDefinition does (while FieldItemBase extends Map class),
 * or extend ComplexDataDefinitionBase as MapDataDefinition does.
 */
class ResourceDataDefinition extends MapDataDefinition {

  // @todo: use Resource plugin ::propertyDefinitions()
  // @see BaseFieldDefinition
  // @see FieldItemDataDefinition
  // function create() {}

/*
  // @todo: implement the following methods
  //    also check other methods of FieldItemDataDefinition class
  public function getMainPropertyName() {
    //return $this->fieldDefinition->getFieldStorageDefinition()->getMainPropertyName();
  }

  public function getPropertyDefinition($name) {
    //return $this->fieldDefinition->getFieldStorageDefinition()->getPropertyDefinition($name);
  }

*/

  /**
   * An array of resource property definitions.
   *
   * @var \Drupal\Core\TypedData\DataDefinitionInterface[]
   *
   * @see \Drupal\Core\TypedData\ComplexDataDefinitionInterface::getPropertyDefinitions()
   */
  protected $propertyDefinitions;

  // @todo: override 'create()' to make type required



  // @todo: override getPropertyDefinitions() @see MapDataDefinition

  public function getPropertyDefinitions() {
    if (!isset($this->propertyDefinitions)) {
      $class = $this->getResourceClass();

      $this->propertyDefinitions = $class::propertyDefinitions($this->initial_params);
      //$this->propertyDefinitions = $class::propertyDefinitions($this);
    }

    // @see BaseFieldDefinition

    return $this->propertyDefinitions;
  }

  // @todo: add to interface
  protected function getResourceClass() {
    $type_definition = \Drupal::typedDataManager()
      ->getDefinition($this->getDataType());

    return $type_definition['class'];
  }

  protected $initial_params = [];

  // @todo: add to interface
  // @todo: check BaseFieldDefinition::setInitialValue()
  public function setInitialParams($initial_params) {
    $this->initial_params = $initial_params;
  }
  public function getInitialParams() {
    // @todo: store in $this->definition['initial_params']
    return $this->initial_params;
  }

}
