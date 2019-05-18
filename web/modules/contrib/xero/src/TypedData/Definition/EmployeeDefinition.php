<?php

namespace Drupal\xero\TypedData\Definition;

use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\ComplexDataDefinitionBase;

/**
 * Xero Employee data definition
 */
class EmployeeDefinition extends ComplexDataDefinitionBase implements XeroDefinitionInterface {
  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions() {
    if (!isset($this->propertyDefinitions)) {
      $info = &$this->propertyDefinitions;

      // UUID is read only.
      $info['EmployeeID'] = DataDefinition::create('string')->setLabel('Employee Id')->addConstraint('XeroGuidConstraint');

      // Writeable properties.
      $info['FirstName'] = DataDefinition::create('string')->setLabel('First name');
      $info['LastName'] = DataDefinition::create('string')->setLabel('Last name');

      // Optional.
      $info['ExternalLink'] = LinkDefinition::create('xero_link')->setLabel('External link');

      // Read-only.
      $info['Status'] = DataDefinition::create('string')->setLabel('Status')->setReadOnly(TRUE);
    }
    return $this->propertyDefinitions;
  }
}
