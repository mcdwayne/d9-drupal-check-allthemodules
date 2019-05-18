<?php

namespace Drupal\xero\TypedData\Definition;

use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\ComplexDataDefinitionBase;
use Drupal\Core\TypedData\ListDataDefinition;

/**
 * Tracking Category data definition.
 */
class TrackingCategoryDefinition extends ComplexDataDefinitionBase implements XeroDefinitionInterface {
  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions() {
    if (!isset($this->propertyDefinitions)) {
      $info = &$this->propertyDefinitions;

      $info['Name'] = DataDefinition::create('string')->setLabel('Name')->setRequired(TRUE);
      $info['Status'] = DataDefinition::create('string')->setLabel('Status');
      $info['TrackingCategoryID'] = DataDefinition::create('string')->setLabel('Tracking Category ID')->addConstraint('XeroGuidConstraint');
      $info['Options'] = ListDataDefinition::create('xero_tracking_category_option')->setLabel('Options');
    }
    return $this->propertyDefinitions;
  }
}
