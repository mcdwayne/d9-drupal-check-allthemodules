<?php

namespace Drupal\xero\TypedData\Definition;

use Drupal\Core\TypedData\ListDataDefinition;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\ComplexDataDefinitionBase;

/**
 * Xero Invoice data definition
 */
class InvoiceDefinition extends ComplexDataDefinitionBase implements XeroDefinitionInterface {
  /**
   * {@inheritdoc}
   *
   * @todo CreditNotes
   */
  public function getPropertyDefinitions() {
    if (!isset($this->propertyDefinitions)) {
      $info = &$this->propertyDefinitions;

      $type_options = array('choices' => array('ACCREC', 'ACCPAY'));
      $line_type_options = array('choices' => array('Exclusive', 'Inclusive', 'NoTax'));
      $status_options = array('choices' => array('DRAFT', 'SUBMITTED', 'DELETED', 'AUTHORISED', 'PAID', 'INVOICED'));

      // UUID is read only.
      $info['InvoiceID'] = DataDefinition::create('string')->setLabel('Invoice ID')->addConstraint('XeroGuidConstraint');

      // Writeable properties.
      $info['Type'] = DataDefinition::create('string')->setRequired(TRUE)->setLabel('Type')->addConstraint('XeroChoiceConstraint', $type_options);
      $info['Contact'] = ContactDefinition::create('xero_contact')->setRequired(TRUE)->setLabel('Contact');
      $info['LineItems'] = ListDataDefinition::create('xero_line_item')->setRequired(TRUE)->setLabel('Line Items');

      // Recommended
      $info['Date'] = DataDefinition::create('string')->setLabel('Date')->addConstraint('Date');
      $info['DueDate'] = DataDefinition::create('string')->setLabel('Due Date')->addConstraint('Date');
      $info['LineAmountTypes'] = DataDefinition::create('string')->setLabel('Line Amount Type')->addConstraint('XeroChoiceConstraint', $line_type_options);

      // Optional
      $info['InvoiceNumber'] = DataDefinition::create('string')->setLabel('Invoice #');
      $info['Reference'] = DataDefinition::create('string')->setLabel('Reference');
      $info['BrandingThemeID'] = DataDefinition::create('string')->setLabel('Branding Theme ID')->addConstraint('XeroGuidConstraint');
      $info['Url'] = DataDefinition::create('uri')->setLabel('URL');
      $info['CurrencyCode'] = DataDefinition::create('string')->setLabel('Currency code');
      $info['CurrencyRate'] = DataDefinition::create('float')->setLabel('Currency rate');
      $info['Status'] = DataDefinition::create('string')->setLabel('Status')->addConstraint('XeroChoiceConstraint', $status_options);
      $info['SentToContact'] = DataDefinition::create('boolean')->setLabel('Sent?');
      $info['ExpectedPaymentDate'] = DataDefinition::create('datetime_iso8601')->setLabel('Expected Payment Date');
      $info['PlannedPaymentDate'] = DataDefinition::create('datetime_iso8601')->setLabel('Planned Payment Date');
      $info['SubTotal'] = DataDefinition::create('float')->setLabel('Sub-Total');
      $info['TotalTax'] = DataDefinition::create('float')->setLabel('Total Tax');
      $info['Total'] = DataDefinition::create('float')->setLabel('Total');
      $info['TotalDiscount'] = DataDefinition::create('float')->setLabel('Total Discount');

      // Read-only
      $info['HasAttachments'] = DataDefinition::create('boolean')->setLabel('Has Attachments?')->setReadOnly(TRUE);
      $info['Payments'] = ListDataDefinition::create('xero_payment')->setLabel('Payments')->setReadOnly(TRUE);
      $info['AmountDue'] = DataDefinition::create('float')->setLabel('Amount Due')->setReadOnly(TRUE);
      $info['AmountPaid'] = DataDefinition::create('float')->setLabel('Amount Paid')->setReadOnly(TRUE);
      $info['AmountCredited'] = DataDefinition::create('float')->setLabel('Amount Credited')->setReadOnly(TRUE);
      $info['UpdatedDateUTC'] = DataDefinition::create('datetime_iso8601')->setLabel('Updated Date')->setReadOnly(TRUE);
    }
    return $this->propertyDefinitions;
  }
}
