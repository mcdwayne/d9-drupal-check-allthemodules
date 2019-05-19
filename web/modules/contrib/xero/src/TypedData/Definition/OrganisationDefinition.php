<?php

namespace Drupal\xero\TypedData\Definition;

use Drupal\Core\TypedData\ComplexDataDefinitionBase;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\ListDataDefinition;

/**
 * Xero Organisation definition
 */
class OrganisationDefinition extends ComplexDataDefinitionBase implements XeroDefinitionInterface {

  /**
   * @const array
   */
  static public $VERSIONTYPES = [
    'AU', 'NZ', 'GLOBAL', 'UK', 'US', 'AUONRAMP', 'NZONRAMP', 'GLOBALONRAMP',
    'UKONRAMP', 'USONRAMP',
  ];

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions() {
    if (!isset($this->propertyDefinitions)) {
      $info = &$this->propertyDefinitions;

      $info['APIKey'] = DataDefinition::create('string')
        ->setLabel('API Key')
        ->setReadOnly(TRUE);
      $info['Name'] = DataDefinition::create('string')
        ->setLabel('Name')
        ->setReadOnly(TRUE);
      $info['LegalName'] = DataDefinition::create('string')
        ->setLabel('Legal Name')
        ->setReadOnly(TRUE);
      $info['PaysTax'] = DataDefinition::create('boolean')
        ->setLabel('Pays Tax')
        ->setReadOnly(TRUE);
      $info['Version'] = DataDefinition::create('string')
        ->setLabel('Version')
        ->setReadOnly(TRUE)
        ->addConstraint('Choice', ['choices' => self::$VERSIONTYPES]);
      $info['BaseCurrency'] = DataDefinition::create('string')
        ->setLabel('Base Currency')
        ->setReadOnly(TRUE);
      $info['CountryCode'] = DataDefinition::create('string')
        ->setLabel('Country Code')
        ->setReadOnly(TRUE);
      $info['IsDemoCompany'] = DataDefinition::create('boolean')
        ->setLabel('Demo Company')
        ->setReadOnly(TRUE);
      $info['OrganisationStatus'] = DataDefinition::create('string')
        ->setLabel('Status')
        ->setReadOnly(TRUE)
        ->addConstraint('Choice', ['ACTIVE']);
      $info['RegistrationNumber'] = DataDefinition::create('string')
        ->setLabel('Registration Number')
        ->setReadOnly(TRUE);
      $info['TaxNumber'] = DataDefinition::create('string')
        ->setLabel('Tax Number')
        ->setReadOnly(TRUE);
      $info['FinancialYearEndDay'] = DataDefinition::create('string')
        ->setLabel('Fiscal Year Last Day')
        ->setReadOnly(TRUE);
      $info['FinancialYearEndMonth'] = DataDefinition::create('integer')
        ->setLabel('Fiscal Year Last Month')
        ->setReadOnly(TRUE);
      $info['SalesTaxBasis'] = DataDefinition::create('string')
        ->setLabel('Sales Tax Basis')
        ->setReadOnly(TRUE)
        ->addConstraint('Choice', ['choices' => [
          'PAYMENTS', 'INVOICE', 'CASH', 'ACCRUAL', 'FLATRATECASH', 'FLATRATEACCRUAL', 'ACCRUALS', 'NONE',
        ]]);
      $info['SalesTaxPeriod'] = DataDefinition::create('string')
        ->setLabel('Sales Tax Period')
        ->setReadOnly(TRUE)
        ->addConstraint('Choice', ['choices' => [
          'MONTHLY', 'QUARTERLY1', 'QUARTERLY2', 'QUARTERLY3', 'ANNUALLY', 'ONEMONTHS',
          'TWOMONTHS', 'SIXMONTHS', '1MONTHLY', '2MONTHLY', '3MONTHLY', '6MONTHLY',
          'QUARTERLY',
        ]]);
      $info['PeriodLockDate'] = DataDefinition::create('datetime_iso8601')
        ->setLabel('Period Lock Date')
        ->setReadOnly(TRUE);
      $info['EndOfYearLockDate'] = DataDefinition::create('datetime_iso8601')
        ->setLabel('End of Year Lock Date')
        ->setReadOnly(TRUE);
      $info['CreatedDateUTC'] = DataDefinition::create('datetime_iso8601')
        ->setLabel('Created Date')
        ->setReadOnly(TRUE);
      $info['OrganisationEntityType'] = DataDefinition::create('string')
        ->setLabel('Type')
        ->setReadOnly(TRUE)
        ->addConstraint('Choice', ['choices' => [
          'COMPANY', 'CHARITY', 'CLUBSOCIETY', 'PARTNERSHIP', 'PRACTICE',
          'SOLETRADER', 'TRUST'
        ]]);
      $info['Timezone'] = DataDefinition::create('string')
        ->setLabel('Timezone')
        ->setReadOnly(TRUE);
      $info['ShortCode'] = DataDefinition::create('string')
        ->setLabel('Short Code')
        ->setReadOnly(TRUE);
      $info['LineOfBusiness'] = DataDefinition::create('string')
        ->setLabel('Line of Business')
        ->setReadOnly(TRUE);
      $info['Addresses'] = ListDataDefinition::create('xero_address')
        ->setLabel('Address')
        ->setReadOnly(TRUE);
      $info['Phones'] = ListDataDefinition::create('xero_phone')
        ->setLabel('Phone Number')
        ->setReadOnly(TRUE);
      $info['ExternalLinks'] = ListDataDefinition::create('xero_link')
        ->setLabel('External Links')
        ->setReadOnly(TRUE);
    }
    return $this->propertyDefinitions;
  }
}