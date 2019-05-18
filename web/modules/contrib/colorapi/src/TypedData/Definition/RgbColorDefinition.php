<?php

namespace Drupal\colorapi\TypedData\Definition;

use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\MapDataDefinition;

/**
 * Definition class for Typed Data API RGB Color Complex Data types.
 */
class RgbColorDefinition extends MapDataDefinition {

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions() {
    if (!isset($this->propertyDefinitions)) {
      $this->propertyDefinitions['red'] = DataDefinition::create('integer')
        ->setLabel('Red')
        ->addConstraint('Range', ['min' => 0, 'max' => 255])
        ->setDescription('The red value of the RGB color')
        ->setRequired(TRUE);
      $this->propertyDefinitions['green'] = DataDefinition::create('integer')
        ->setLabel('Green')
        ->addConstraint('Range', ['min' => 0, 'max' => 255])
        ->setDescription('The red value of the RGB color')
        ->setRequired(TRUE);
      $this->propertyDefinitions['blue'] = DataDefinition::create('string')
        ->setLabel('Blue')
        ->addConstraint('Range', ['min' => 0, 'max' => 255])
        ->setDescription('The blue value of the RGB color')
        ->setRequired(TRUE);
    }

    return $this->propertyDefinitions;
  }

}
