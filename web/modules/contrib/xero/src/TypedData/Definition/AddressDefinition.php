<?php

namespace Drupal\xero\TypedData\Definition;

use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\ComplexDataDefinitionBase;

/**
 * Xero Address data definition
 */
class AddressDefinition extends ComplexDataDefinitionBase implements XeroDefinitionInterface {
  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions() {
    if (!isset($this->propertyDefinitions)) {
      $info = &$this->propertyDefinitions;
      $options = array('choices' => array('POBOX', 'STREET', 'DELIVERY'));
      $info['AddressType'] = DataDefinition::create('string')->setLabel('Type')->setRequired(TRUE)->addConstraint('XeroChoiceConstraint', $options);
      $info['AddressLine1'] = DataDefinition::create('string')->setLabel('Address Line 1');
      $info['AddressLine2'] = DataDefinition::create('string')->setLabel('Address Line 2');
      $info['AddressLine3'] = DataDefinition::create('string')->setLabel('Address Line 3');
      $info['AddressLine4'] = DataDefinition::create('string')->setLabel('Address Line 4');
      $info['City'] = DataDefinition::create('string')->setLabel('City');
      $info['Region'] = DataDefinition::create('string')->setLabel('Region');
      $info['PostalCode'] = DataDefinition::create('string')->setLabel('Postal code');
      $info['Country'] = DataDefinition::create('string')->setLabel('Country');
      $info['AttentionTo'] = DataDefinition::create('string')->setLabel('Attention to');
    }
    return $this->propertyDefinitions;
  }
}
