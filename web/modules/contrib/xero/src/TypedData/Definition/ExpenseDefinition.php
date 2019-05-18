<?php

namespace Drupal\xero\TypedData\Definition;

use Drupal\Core\TypedData\ListDataDefinition;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\ComplexDataDefinitionBase;

/**
 * Xero Expense Claim data definition
 */
class ExpenseDefinition extends ComplexDataDefinitionBase implements XeroDefinitionInterface {
  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions() {
    if (!isset($this->propertyDefinitions)) {
      $info = &$this->propertyDefinitions;

      // UUID is read only.
      $info['ExpenseClaimID'] = DataDefinition::create('string')->setLabel('Expense Claim ID')->addConstraint('XeroGuidConstraint');

      // Writeable properties.
      $info['User'] = UserDefinition::create('xero_user')->setRequired(TRUE)->setLabel('User');
      $info['Receipts'] = ListDataDefinition::create('xero_receipt')->setRequired(TRUE)->setLabel('Receipts');

      // Read-only
      $info['ExpenseNumber'] = DataDefinition::create('string')->setLabel('Expense #')->setReadOnly(TRUE);
      $info['Status'] = DataDefinition::create('string')->setLabel('Status')->setReadOnly(TRUE);
      $info['UpdatedDateUTC'] = DataDefinition::create('datetime_iso8601')->setLabel('Updated Date')->setReadOnly(TRUE);
      $info['Total'] = DataDefinition::create('float')->setLabel('Total')->setReadOnly(TRUE);
      $info['AmountDue'] = DataDefinition::create('float')->setLabel('Amount Due')->setReadOnly(TRUE);
      $info['AmountPaid'] = DataDefinition::create('float')->setLabel('Amount Paid')->setReadOnly(TRUE);
      $info['PaymentDueDate'] = DataDefinition::create('string')->setLabel('Payment Due Date')->setReadOnly(TRUE);
      $info['ReportingDate'] = DataDefinition::create('string')->setLabel('Reporting Date')->setReadOnly(TRUE);
    }
    return $this->propertyDefinitions;
  }
}
