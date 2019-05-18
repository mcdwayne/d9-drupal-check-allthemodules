<?php

namespace Drupal\xero\TypedData\Definition;

use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\ComplexDataDefinitionBase;
use Robo\State\Data;

/**
 * Tracking Category data definition.
 */
class TrackingOptionDefinition extends ComplexDataDefinitionBase implements XeroDefinitionInterface {
  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions() {
    if (!isset($this->propertyDefinitions)) {
      $info = &$this->propertyDefinitions;

      $info['Name'] = DataDefinition::create('string')->setLabel('Name of the Xero Tracking Category')->setRequired(TRUE);
      $info['Option'] = DataDefinition::create('string')->setLabel('An option within a Xero Tracking Category')->setRequired(TRUE);
    }
    return $this->propertyDefinitions;
  }
}
