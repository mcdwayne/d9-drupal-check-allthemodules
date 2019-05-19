<?php

namespace Drupal\xero\TypedData\Definition;

use Drupal\Core\TypedData\ListDataDefinition;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\ComplexDataDefinitionBase;

/**
 * Xero Credit Note data definition
 */
class CreditDefinition extends ComplexDataDefinitionBase implements XeroDefinitionInterface {
  /**
   * {@inheritdoc}
   *
   * @todo Allocations
   * @todo confirm read-only fields as API documentation is incomplete
   *
   * http://developer.xero.com/documentation/api/credit-notes/
   */
  public function getPropertyDefinitions() {
    if (!isset($this->propertyDefinitions)) {
      $info = &$this->propertyDefinitions;

      $type_options = array('choices' => array('ACCRECCREDIT', 'ACCPAYCREDIT'));
      $line_type_options = array('choices' => array('Exclusive', 'Inclusive', 'NoTax'));
      $status_options = array('choices' => array('DRAFT', 'SUBMITTED', 'DELETED', 'AUTHORISED', 'PAID', 'INVOICED'));

      // UUID is read only.
      $info['CreditNoteID'] = DataDefinition::create('string')->setLabel('Credit Note ID')->addConstraint('XeroGuidConstraint');

      // Writeable properties.
      $info['Type'] = DataDefinition::create('string')->setRequired(TRUE)->setLabel('Type')->addConstraint('XeroChoiceConstraint', $type_options);
      $info['Contact'] = ContactDefinition::create('xero_contact')->setRequired(TRUE)->setLabel('Contact');

      // Recommended
      $info['Date'] = DataDefinition::create('string')->setLabel('Date')->addConstraint('Date');
      $info['LineAmountTypes'] = DataDefinition::create('string')->setLabel('Line Amount Type')->addConstraint('XeroChoiceConstraint', $line_type_options);
      $info['LineItems'] = ListDataDefinition::create('xero_line_item')->setRequired(TRUE)->setLabel('Line Items');

      // Optional
      $info['Reference'] = DataDefinition::create('string')->setLabel('Reference');
      $info['Status'] = DataDefinition::create('string')->setLabel('Status')->addConstraint('XeroChoiceConstraint', $status_options);
      $info['CreditNoteNumber'] = DataDefinition::create('string')->setLabel('Credit Note #');
      $info['SubTotal'] = DataDefinition::create('float')->setLabel('Sub-Total');
      $info['TotalTax'] = DataDefinition::create('float')->setLabel('Total Tax');
      $info['Total'] = DataDefinition::create('float')->setLabel('Total');
      $info['CurrencyCode'] = DataDefinition::create('string')->setLabel('Currency code');
      $info['BrandingThemeID'] = DataDefinition::create('string')->setLabel('Branding Theme ID')->addConstraint('XeroGuidConstraint');
      $info['SentToContact'] = DataDefinition::create('boolean')->setLabel('Sent to Contact?');

      // Read-only
      $info['UpdatedDateUTC'] = DataDefinition::create('datetime_iso8601')->setLabel('Updated Date')->setReadOnly(TRUE);
      $info['FullyPaidOnDate'] = DataDefinition::create('datetime_iso8601')->setlabel('Fully Paid On Date')->setReadOnly(TRUE);
    }
    return $this->propertyDefinitions;
  }
}
