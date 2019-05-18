<?php

namespace Drupal\xero\TypedData\Definition;

use Drupal\Core\TypedData\ComplexDataDefinitionBase;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Xero Branding Theme definition
 */
class BrandingThemeDefinition extends ComplexDataDefinitionBase implements XeroDefinitionInterface {

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions() {
    if (!isset($this->propertyDefinitions)) {
      $info = &$this->propertyDefinitions;

      $info['BrandingThemeID'] = DataDefinition::create('string')
        ->setLabel('Branding Theme ID')
        ->addConstraint('XeroGuidConstraint');
      $info['Name'] = DataDefinition::create('string')
        ->setLabel('Label');
      $info['SortOrder'] = DataDefinition::create('integer')
        ->setLabel('Sort Order');
      $info['CreatedDateUTC'] = DataDefinition::create('datetime_iso8601')
        ->setLabel('Created Date');
    }
    return $this->propertyDefinitions;
  }
}