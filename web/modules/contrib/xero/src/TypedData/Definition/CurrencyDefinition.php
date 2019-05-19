<?php

namespace Drupal\xero\TypedData\Definition;

use Drupal\Core\TypedData\ComplexDataDefinitionBase;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Xero Currency Definition
 */
class CurrencyDefinition extends ComplexDataDefinitionBase implements XeroDefinitionInterface {

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions() {
    if (!isset($this->propertyDefinitions)) {
      $info = &$this->propertyDefinitions;

      $info['Code'] = DataDefinition::create('string')
        ->setLabel('Code')
        ->addConstraint('Length', ['min' => 3, 'max' => 3]);
      $info['Description'] = DataDefinition::create('string')
        ->setLabel('Description');
    }
    return $this->propertyDefinitions;
  }
}