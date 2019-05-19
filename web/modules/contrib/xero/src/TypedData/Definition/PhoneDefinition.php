<?php

namespace Drupal\xero\TypedData\Definition;

use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\ComplexDataDefinitionBase;

/**
 * Xero Phone Number data definition
 */
class PhoneDefinition extends ComplexDataDefinitionBase implements XeroDefinitionInterface {
  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions() {
    if (!isset($this->propertyDefinitions)) {
      $info = &$this->propertyDefinitions;
      $options = array('choices' => array('DEFAULT', 'DDI', 'MOBILE', 'FAX'));
      $info['PhoneType'] = DataDefinition::create('string')->setLabel('Type')->setRequired(TRUE)->addConstraint('XeroChoiceConstraint', $options);
      $info['PhoneNumber'] = DataDefinition::create('string')->setLabel('Number')->addConstraint('Length', array('max' => 50));
      $info['PhoneAreaCode'] = DataDefinition::create('string')->setLabel('Area code')->addConstraint('Length', array('max' => 10));
      $info['PhoneCountryCode'] = DataDefinition::create('string')->setLabel('Country code')->addConstraint('Length', array('max' => 20));
    }
    return $this->propertyDefinitions;
  }
}
