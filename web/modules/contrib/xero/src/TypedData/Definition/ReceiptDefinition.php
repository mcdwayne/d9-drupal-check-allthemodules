<?php

namespace Drupal\xero\TypedData\Definition;

use Drupal\Core\TypedData\ListDataDefinition;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\ComplexDataDefinitionBase;

/**
 * Xero Receipt data definition
 */
class ReceiptDefinition extends ComplexDataDefinitionBase implements XeroDefinitionInterface {
  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions() {
    if (!isset($this->propertyDefinitions)) {
      $info = &$this->propertyDefinitions;

      $line_type_options = array('choices' => array('Exclusive', 'Inclusive', 'NoTax'));

      // UUID is read only.
      $info['ReceiptID'] = DataDefinition::create('string')->setLabel('Receipt ID')->addConstraint('XeroGuidConstraint');

      // Writeable properties.
      $info['Date'] = DataDefinition::create('string')->setLabel('Date')->addConstraint('Date')->setRequired(TRUE);
      $info['Contact'] = ContactDefinition::create('xero_contact')->setRequired(TRUE)->setLabel('Contact');
      $info['LineItems'] = ListDataDefinition::create('xero_line_item')->setRequired(TRUE)->setLabel('Line Items');
      $info['User'] = UserDefinition::create('xero_user')->setRequired(TRUE)->setLabel('User');

      // Optional
      $info['Reference'] = DataDefinition::create('string')->setLabel('Reference');
      $info['LineAmountTypes'] = DataDefinition::create('string')->setLabel('Line Amount Type')->addConstraint('XeroChoiceConstraint', $line_type_options);
      $info['SubTotal'] = DataDefinition::create('float')->setLabel('Sub-Total');
      $info['TotalTax'] = DataDefinition::create('float')->setLabel('Total Tax');
      $info['Total'] = DataDefinition::create('float')->setLabel('Total');

      // Read-only
      $info['ReceiptNumber'] = DataDefinition::create('string')->setLabel('Receipt #')->setReadOnly(TRUE);
      $info['Url'] = DataDefinition::create('uri')->setLabel('URL')->setReadOnly(TRUE);
      $info['Status'] = DataDefinition::create('string')->setLabel('Status')->setReadOnly(TRUE);
      $info['UpdatedDateUTC'] = DataDefinition::create('datetime_iso8601')->setLabel('Updated Date')->setReadOnly(TRUE);
      $info['HasAttachments'] = DataDefinition::create('boolean')->setLabel('Has Attachments?')->setReadOnly(TRUE);
    }
    return $this->propertyDefinitions;
  }
}
