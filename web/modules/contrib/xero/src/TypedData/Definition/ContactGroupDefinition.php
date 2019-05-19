<?php

namespace Drupal\xero\TypedData\Definition;

use Drupal\Core\TypedData\ComplexDataDefinitionBase;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\ListDataDefinition;

/**
 * Xero Contact Group definition
 */
class ContactGroupDefinition extends ComplexDataDefinitionBase implements XeroDefinitionInterface {

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions() {
    if (!isset($this->propertyDefinitions)) {
      $info = &$this->propertyDefinitions;

      $options = ['ACTIVE', 'DELETED'];

      $info['ContactGroupID'] = DataDefinition::create('string')
        ->setLabel('Contact Group ID')
        ->addConstraint('XeroGuidConstraint');
      $info['Name'] = DataDefinition::create('string')
        ->setLabel('Name')
        ->setRequired(TRUE);
      $info['Status'] = ListDataDefinition::create('string')
        ->setLabel('Status')
        ->addConstraint('Choice', ['choices' => $options]);
      $info['Contacts'] = ListDataDefinition::create('xero_contact')
        ->setLabel('Contacts');
    }
    return $this->propertyDefinitions;
  }
}