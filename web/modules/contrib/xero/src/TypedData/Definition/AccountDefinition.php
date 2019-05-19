<?php

namespace Drupal\xero\TypedData\Definition;

use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\ComplexDataDefinitionBase;
use Drupal\xero\TaxTypeTrait;

/**
 * Xero Account data definition
 */
class AccountDefinition extends ComplexDataDefinitionBase implements XeroDefinitionInterface {

  use TaxTypeTrait;

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions() {
    if (!isset($this->propertyDefinitions)) {
      $info = &$this->propertyDefinitions;
      $type_options = array('choices' => $this->getAccountTypes());
      $tax_type_options = array('choices' => self::getTaxTypes());
      $class_options = array('choices' => array('ASSET', 'EQUITY', 'EXPENSE', 'LIABILITY', 'REVENUE'));
      $status_options = array('choices' => array('ACTIVE', 'ARCHIVED'));

      // UUID is read only.
      $info['AccountID'] = DataDefinition::create('string')->setLabel('Account ID')->addConstraint('XeroGuidConstraint');

      // Writeable properties.
      $info['Code'] = DataDefinition::create('string')->setLabel('Code');
      $info['Name'] = DataDefinition::create('string')->setRequired(TRUE)->setLabel('Name');
      $info['Type'] = DataDefinition::create('string')->setRequired(TRUE)->setLabel('Type')->addConstraint('XeroChoiceConstraint', $type_options);
      $info['Description'] = DataDefinition::create('string')->setLabel('Description');
      $info['TaxType'] = DataDefinition::create('string')->setLabel('Tax type')->addConstraint('XeroChoiceConstraint', $tax_type_options);
      $info['EnablePaymentsToAccount'] = DataDefinition::create('boolean')->setLabel('May have payments');
      $info['ShowInExpenseClaims'] = DataDefinition::create('boolean')->setLabel('Shown in expense claims');

      // Read-only properties.
      $info['Class'] = DataDefinition::create('string')->setLabel('Class')->setReadOnly(TRUE)->addConstraint('XeroChoiceConstraint', $class_options);
      $info['Status'] = DataDefinition::create('string')->setLabel('Status')->setReadOnly(TRUE)->addConstraint('XeroChoiceConstraint', $status_options);
      $info['SystemAccount'] = DataDefinition::create('string')->setLabel('System account')->setReadOnly(TRUE);
      $info['BankAccountNumber'] = DataDefinition::create('string')->setLabel('Bank account')->setReadOnly(TRUE);
      $info['CurrencyCode'] = DataDefinition::create('string')->setLabel('Currency code')->setReadOnly(TRUE);
      $info['ReportingCode'] = DataDefinition::create('string')->setLabel('Reporting code')->setReadOnly(TRUE);
      $info['ReportingCodeName'] = DataDefinition::create('string')->setLabel('Reporting code name')->setReadOnly(TRUE);
    }
    return $this->propertyDefinitions;
  }

  /**
   * Provide the correct Xero Account Types for validation.
   */
  protected function getAccountTypes() {
    return array(
      'BANK', 'CURRENT', 'CURRLIAB', 'DEPRECIATN', 'DIRECTCOSTS', 'EQUITY',
      'EXPENSE', 'FIXED', 'LIABILITY', 'NONCURRENT', 'OTHERINCOME',
      'OVERHEADS', 'PREPAYMENT', 'REVENUE', 'SALES', 'TERMLIAB',
      'PAYGLIABILITY', 'SUPERANNUATIONEXPENSE', 'SUPERANNUATIONLIABILITY',
      'WAGESEXPENSE',
    );
  }

}
