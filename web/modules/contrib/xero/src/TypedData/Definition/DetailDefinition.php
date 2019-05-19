<?php

namespace Drupal\xero\TypedData\Definition;

use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\ComplexDataDefinitionBase;

/**
 * Xero Detail data definition.
 */
class DetailDefinition extends ComplexDataDefinitionBase implements XeroDefinitionInterface {

  /**
   * {@inheritdoc}
   *
   * @todo additional properties for items - http://developer.xero.com/documentation/api/items/
   */
  public function getPropertyDefinitions() {
    if (!isset($this->propertyDefinitions)) {
      $info = &$this->propertyDefinitions;
      $tax_options = array('choices' => $this->getTaxTypes());

      $info['UnitPrice'] = DataDefinition::create('float')->setLabel('Unit price');
      $info['AccountCode'] = DataDefinition::create('string')->setLabel('Account code');
      $info['TaxType'] = DataDefinition::create('string')->setLabel('Tax type')->addConstraint('XeroChoiceConstraint', $tax_options);
    }
    return $this->propertyDefinitions;
  }

  /**
   * Provide the correct Xero Tax Types for validation.
   */
  protected function getTaxTypes() {
    return array(
      // Global types
      'INPUT', 'OUTPUT', 'NONE', 'GSTONIMPORTS',
      // Aussie
      'CAPEXINPUT', 'EXEMPTEXPORT', 'EXEMPTEXPENSES', 'EXEMPTCAPITAL',
      'EXEMPTOUTPUT', 'INPUTTAXED', 'BASEXCLUDED', 'GSTONCAPIMPORTS',
      'GSTONIMPORTS',
      // Kiwi
      'INPUT2', 'OUTPUT2', 'ZERORATED',
      // Brit
      'CAPEXINPUT2', 'CAPEXOUTPUT', 'CAPEXOUTPUT2', 'CAPEXSRINPUT',
      'CAPEXSROUTPUT', 'ECZRINPUT', 'ECZROUTPUT', 'ECZROUTPUTSERVICES',
      'EXEMPTINPUT', 'RRINPUT', 'RROUTPUT', 'SRINPUT', 'SROUTPUT',
      'ZERORATEDINPUT', 'ZERORATEDOUTPUT',
    );
  }
}
