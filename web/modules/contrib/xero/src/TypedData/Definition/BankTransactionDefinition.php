<?php

namespace Drupal\xero\TypedData\Definition;

use Drupal\Core\TypedData\ListDataDefinition;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\ComplexDataDefinitionBase;

/**
 * Xero BankTransaction data definition
 */
class BankTransactionDefinition extends ComplexDataDefinitionBase implements XeroDefinitionInterface {
  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions() {
    if (!isset($this->propertyDefinitions)) {
      $info = &$this->propertyDefinitions;

      $type_options = array('choices' => array('RECEIVE', 'SPEND'));
      $line_type_options = array('choices' => array('Exclusive', 'Inclusive', 'NoTax'));$status_options = array('choices' => array('DELETED', 'AUTHORISED'));

      // UUID is read only.
      $info['BankTransactionID'] = DataDefinition::create('string')->setLabel('Bank Transaction ID')->addConstraint('XeroGuidConstraint');

      // Writeable properties.
      $info['Type'] = DataDefinition::create('string')->setRequired(TRUE)->setLabel('Type')->addConstraint('XeroChoiceConstraint', $type_options);
      $info['Contact'] = ContactDefinition::create('xero_contact')->setRequired(TRUE)->setLabel('Contact');
      $info['LineItems'] = ListDataDefinition::create('xero_line_item')->setRequired(TRUE)->setLabel('Line Items');
      $info['BankAccount'] = AccountDefinition::create('xero_account')->setRequired(TRUE)->setLabel('Bank Account');

      $info['DueDate'] = DataDefinition::create('string')->setLabel('Due Date')->addConstraint('Date');
      $info['LineAmountTypes'] = DataDefinition::create('string')->setLabel('Line Amount Type')->addConstraint('XeroChoiceConstraint', $line_type_options);

      // Optional
      $info['IsReconciled'] = DataDefinition::create('boolean')->setLabel('Is reconciled?');
      $info['Date'] = DataDefinition::create('string')->setLabel('Date')->addConstraint('Date');
      $info['Reference'] = DataDefinition::create('string')->setLabel('Reference');
      $info['CurrencyRate'] = DataDefinition::create('float')->setLabel('Currency rate');
      $info['Url'] = DataDefinition::create('uri')->setLabel('URL');
      $info['Status'] = DataDefinition::create('string')->setLabel('Status')->addConstraint('XeroChoiceConstraint', $status_options);
      $info['SubTotal'] = DataDefinition::create('float')->setLabel('Sub-Total');
      $info['TotalTax'] = DataDefinition::create('float')->setLabel('Total Tax');
      $info['Total'] = DataDefinition::create('float')->setLabel('Total');

      // Read-only
      $info['UpdatedDateUTC'] = DataDefinition::create('datetime_iso8601')->setLabel('Updated Date')->setReadOnly(TRUE);
    }
    return $this->propertyDefinitions;
  }
}
