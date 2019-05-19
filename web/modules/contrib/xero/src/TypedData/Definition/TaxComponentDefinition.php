<?php

namespace Drupal\xero\TypedData\Definition;

use Drupal\Core\TypedData\ComplexDataDefinitionBase;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Xero Tax Component Definition
 */
class TaxComponentDefinition extends ComplexDataDefinitionBase implements XeroDefinitionInterface {

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions() {
    if (!isset($this->propertyDefinitions)) {
      $info = &$this->propertyDefinitions;

      $info['Name'] = DataDefinition::create('string')
        ->setLabel('Name')
        ->setRequired(TRUE);
      $info['Rate'] = DataDefinition::create('float')
        ->setLabel('Rate')
        ->setRequired(TRUE);
      $info['IsCompound'] = DataDefinition::create('boolean')
        ->setLabel('Is Compound?')
        ->setRequired(TRUE);
    }
    return $this->propertyDefinitions;
  }
}