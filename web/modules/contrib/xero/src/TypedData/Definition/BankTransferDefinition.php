<?php

namespace Drupal\xero\TypedData\Definition;

use Drupal\Core\TypedData\ComplexDataDefinitionBase;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Xero Bank Transfer definition
 */
class BankTransferDefinition extends ComplexDataDefinitionBase implements XeroDefinitionInterface {

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions() {
    if (!isset($this->propertyDefinitions)) {
      $info = &$this->propertyDefinitions;

      $info['BankTransferID'] = DataDefinition::create('string')
        ->setLabel('Bank Transfer ID')
        ->addConstraint('XeroGuidConstraint');

      // Required properties.
      $info['FromBankAccount'] = AccountDefinition::create('xero_account')
        ->setLabel('From Account')
        ->setRequired(TRUE);
      $info['ToBankAccount'] = AccountDefinition::create('xero_account')
        ->setLabel('To Account')
        ->setRequired(TRUE);
      $info['Amount'] = DataDefinition::create('float')
        ->setLabel('Amount')
        ->setRequired(TRUE);

      // Optional property.
      $info['Date'] = DataDefinition::create('string')
        ->setLabel('Date')
        ->addConstraint('Date');

      // Read-only properties.
      $info['CurrencyRate'] = DataDefinition::create('float')
        ->setLabel('Currency Rate')
        ->setReadOnly(TRUE);
      $info['FromBankTransactionID'] = DataDefinition::create('string')
        ->setLabel('From Bank Transaction ID')
        ->setReadOnly(TRUE);
      $info['ToBankTransactionID'] = DataDefinition::create('string')
        ->setLabel('To Bank Transaction ID')
        ->setReadOnly(TRUE);
      $info['HasAttachments'] = DataDefinition::create('boolean')
        ->setLabel('Has Attachments')
        ->setReadOnly(TRUE);
      $info['CreatedDateUTC'] = DataDefinition::create('datetime_iso8601')
        ->setLabel('Created Date')
        ->setReadOnly(TRUE);

    }
    return $this->propertyDefinitions;
  }
}