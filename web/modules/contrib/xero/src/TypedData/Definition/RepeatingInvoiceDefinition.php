<?php

namespace Drupal\xero\TypedData\Definition;

use Drupal\Core\TypedData\DataDefinition;
use Drupal\xero\TypedData\Definition\InvoiceDefinition;

/**
 * Xero Repeating Invoice definition
 */
class RepeatingInvoiceDefinition extends InvoiceDefinition implements XeroDefinitionInterface {

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions() {
    if (!isset($this->propertyDefinitions)) {
      $info = &$this->propertyDefinitions;
      $info = parent::getPropertyDefinitions();

      $info['RepeatingInvoiceID'] = $info['InvoiceID'];

      // Remove invoice properties not on repeating invoices.
      unset($info['InvoiceID']);
      unset($info['Url']);
      unset($info['InvoiceNumber']);
      unset($info['CurrencyRate']);
      unset($info['SentToContact']);
      unset($info['ExpectedPaymentDate']);
      unset($info['PlannedPaymentDate']);
      unset($info['TotalDiscount']);
      unset($info['AmountDue']);
      unset($info['AmountPaid']);
      unset($info['AmountCredited']);
      unset($info['CreditNotes']);
      unset($info['UpdatedDateUTC']);

      // Add repeating invoice specific properties.
      $info['Schedule'] = ScheduleDefinition::create('xero_schedule')
        ->setLabel('Schedule');

      // Set all properties as read-only.
      foreach ($info as $key => $property) {
        /** @var \Drupal\Core\TypedData\DataDefinitionInterface $property */
        $property->setReadOnly(TRUE);
      }
    }
    return $this->propertyDefinitions;
  }
}