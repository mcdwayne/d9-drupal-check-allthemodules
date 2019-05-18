<?php

namespace Drupal\xero\TypedData\Definition;
use Drupal\Core\TypedData\ComplexDataDefinitionBase;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\ListDataDefinition;
use Symfony\Component\Security\Core\Tests\Authentication\Provider\DaoAuthenticationProviderTest;

/**
 * Xero Tax Rate Definition
 */
class TaxRateDefinition extends ComplexDataDefinitionBase implements XeroDefinitionInterface {

  static public $TAXTYPES = [
    'OUTPUT', 'INPUT', 'NONE', 'GSTONIMPORTS', 'INPUT2', 'ZERORATED', 'OUTPUT2',
    'CAPEXINPUT', 'CAPEXINPUT2', 'CAPEXOUTPUT', 'CAPEXOUTPUT2', 'CAPEXSRINPUT',
    'CAPEXSROUTPUT', 'ECZRINPUT', 'ECZROUPUT', 'ECZROUTPUTSERVICES',
    'EXEMPTINPUT', 'EXEMPTOUTPUT', 'RRINPUT', 'RROUTPUT', 'SRINPUT', 'SROUTPUT',
    'ZERORATEDINPUT', 'ZERORATEDOUTPUT', 'EXEMPTEXPORT', 'EXEMPTEXPENSES',
    'EXEMPTCAPITAL', 'INPUTTAXED', 'BASEXCLUDED', 'GSTONCAPIMPORTS'
  ];

  static public $REPORTTYPES = [
    'OUTPUT', 'INPUT', 'EXEMPTOUTPUT', 'INPUTTAXED', 'BASEXCLUDED',
    'EXEMPTEXPENSES', 'EXEMPTCAPITAL', 'EXEMPTEXPORT', 'CAPITALEXINPUT',
    'GSTONCAPIMPORTS', 'GSTONIMPORTS', 'EXEMPTINPUT', 'NONE', 'ECOUTPUT',
    'ECOUTPUTSERVICES', 'ECINPUT', 'ECACQUISITIONS', 'CAPITALSALESOUTPUT',
    'CAPITALEXPENSESINPUT', 'MOSSSALES', 'REVERSECHARGES',
  ];

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions() {
    if (!isset($this->propertyDefinitions)) {
      $info = &$this->propertyDefinitions;

      $info['Name'] = DataDefinition::create('string')
        ->setLabel('Name')
        ->setRequired(TRUE);
      $info['TaxType'] = DataDefinition::create('string')
        ->setLabel('Type')
        ->addConstraint('Choice', ['choices' => self::$TAXTYPES]);
      $info['TaxComponents'] = ListDataDefinition::create('xero_tax_component')
        ->setLabel('Components')
        ->setRequired(TRUE);
      $info['Status'] = DataDefinition::create('string')
        ->setLabel('Status')
        ->addConstraint('Choice', ['choices' => ['DELETED', 'ACTIVE', 'ARCHIVED']]);
      $info['ReportTaxType'] = DataDefinition::create('string')
        ->setLabel('Report Tax Type')
        ->setDescription('Required in Australia, New Zealand and United Kingdom.')
        ->addConstraint('Choice', ['choices' => self::$REPORTTYPES]);
      $info['CanApplyToAssets'] = DataDefinition::create('boolean')
        ->setLabel('Can Apply to Assets')
        ->setReadOnly(TRUE);
      $info['CanApplyToEquity'] = DataDefinition::create('boolean')
        ->setLabel('Can Apply to Equity')
        ->setReadOnly(TRUE);
      $info['CanApplyToExpenses'] = DataDefinition::create('boolean')
        ->setLabel('Can Apply to Expenses')
        ->setReadOnly(TRUE);
      $info['CanApplyToLiabilities'] = DataDefinition::create('boolean')
        ->setLabel('Can Apply to Liabilities')
        ->setReadOnly(TRUE);
      $info['CanApplyToRevenue'] = DataDefinition::create('boolean')
        ->setLabel('Can Apply to Revenue')
        ->setReadOnly(TRUE);
      $info['DisplayTaxRate'] = DataDefinition::create('float')
        ->setLabel('Display Tax Rate')
        ->setReadOnly(TRUE);
      $info['EffectiveRate'] = DataDefinition::create('float')
        ->setLabel('Effective Tax Rate')
        ->setReadOnly(TRUE);
    }
    return $this->propertyDefinitions;
  }
}