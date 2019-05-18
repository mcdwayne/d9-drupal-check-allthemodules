<?php

namespace Drupal\xero\TypedData\Definition;

use Drupal\Core\TypedData\ComplexDataDefinitionBase;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Defines the properties for tracking category options.
 */
class TrackingCategoryOptionDefinition extends ComplexDataDefinitionBase implements XeroDefinitionInterface {
  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions() {
    if (!isset($this->propertyDefinitions)) {
      $info = &$this->propertyDefinitions;

      $info['Name'] = DataDefinition::create('string')->setLabel('Name')->setRequired(TRUE);
      $info['Status'] = DataDefinition::create('string')->setLabel('Status');
      $info['TrackingOptionID'] = DataDefinition::create('string')->setLabel('Tracking Category ID')->addConstraint('XeroGuidConstraint');
    }
    return $this->propertyDefinitions;
  }
}