<?php

namespace Drupal\xero\TypedData\Definition;

use Drupal\Core\TypedData\ComplexDataDefinitionBase;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Xero Linked Transaction definition
 */
class LinkedTransactionDefinition extends ComplexDataDefinitionBase implements XeroDefinitionInterface {

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions() {
    if (!isset($this->propertyDefinitions)) {
      $info = &$this->propertyDefinitions;

      $info['LinkedTransactionID'] = DataDefinition::create('string')
        ->setLabel('Linked Transaction ID')
        ->addConstraint('XeroGuidConstraint');

      $info['SourceTransactionID'] = DataDefinition::create('string')
        ->setLabel('Source Transaction ID')
        ->setDescription('The identifier of the source transaction. Either an invoice with a type of ACCPAY or a bank transaction of type SPEND.')
        ->setRequired(TRUE)
        ->addConstraint('XeroGuidConstraint');
      $info['SourceLineItemID'] = DataDefinition::create('string')
        ->setLabel('Source Line Item ID')
        ->setDescription('The line item identifier from the source transaction.')
        ->setRequired(TRUE)
        ->addConstraint('XeroGuidConstraint');

      // Optional properties
      $info['ContactID'] = DataDefinition::create('string')
        ->setLabel('Contact ID')
        ->addConstraint('XeroGuidConstraint');
      $info['TargetTransactionID'] = DataDefinition::create('string')
        ->setLabel('Target Transaction ID')
        ->addConstraint('XeroGuidConstraint');
      $info['TargetLineItemID'] = DataDefinition::create('string')
        ->setLabel('Target Line Item ID')
        ->addConstraint('XeroGuidConstraint');

      // Read only properties.
      $info['Status'] = DataDefinition::create('string')
        ->setLabel('Status')
        ->setReadOnly(TRUE)
        ->addConstraint('Choice', ['choices' => ['DRAFT', 'APPROVED', 'ONDRAFT', 'BILLED', 'VOIDED']]);
      $info['Type'] = DataDefinition::create('string')
        ->setLabel('Type')
        ->setReadOnly(TRUE)
        ->addConstraint('Choice', ['choices' => ['BILLABLEEXPENSE']]);
      $info['UpdatedDateUTC'] = DataDefinition::create('datetime_iso8601')
        ->setLabel('Updated Date')
        ->setReadOnly(TRUE);
      $info['SourceTransactionTypeCode'] = DataDefinition::create('string')
        ->setLabel('Source Transaction Type')
        ->setReadOnly(TRUE)
        ->addConstraint('Choice', ['ACCPAY', 'SPEND']);

    }
    return $this->propertyDefinitions;
  }
}