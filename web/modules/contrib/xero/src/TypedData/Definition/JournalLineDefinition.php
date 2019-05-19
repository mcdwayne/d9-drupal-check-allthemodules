<?php

namespace Drupal\xero\TypedData\Definition;

use Drupal\Core\TypedData\ListDataDefinition;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\ComplexDataDefinitionBase;

/**
 * Xero Journal Line Item data definition.
 */
class JournalLineDefinition extends ComplexDataDefinitionBase implements XeroDefinitionInterface {

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions() {
    if (!isset($this->propertyDefinitions)) {
      $info = &$this->propertyDefinitions;

      // All journal items are read-only.
      $info['JournalLineID'] = DataDefinition::create('string')->setLabel('Journal Line ID')->setReadOnly(TRUE)->addConstraint('XeroGuidConstraint');
      $info['AccountID'] = DataDefinition::create('string')->setLabel('Account ID')->setReadOnly(TRUE)->addConstraint('XeroGuidConstraint');
      $info['AccountCode'] = DataDefinition::create('string')->setLabel('Account code')->setReadOnly(TRUE);
      $info['AccountType'] = DataDefinition::create('string')->setLabel('Account type')->setReadOnly(TRUE);
      $info['AccountName'] = DataDefinition::create('string')->setLabel('Account name')->setReadOnly(TRUE);
      $info['NetAmount'] = DataDefinition::create('float')->setLabel('Net')->setReadOnly(TRUE);
      $info['GrossAmount'] = DataDefinition::create('float')->setLabel('Gross')->setReadOnly(TRUE);
      $info['TaxAmount'] = DataDefinition::create('float')->setLabel('Tax')->setReadOnly(TRUE);
      $info['TaxType'] = DataDefinition::create('string')->setLabel('Tax type')->setReadOnly(TRUE);
      $info['TaxName'] = DataDefinition::create('string')->setLabel('Tax name')->setReadOnly(TRUE);
      $info['TrackingCategories'] = ListDataDefinition::create('xero_tracking')->setLabel('Tracking Categories')->setReadOnly(TRUE);
    }
    return $this->propertyDefinitions;
  }
}
