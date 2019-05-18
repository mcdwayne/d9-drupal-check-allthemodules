<?php

namespace Drupal\xero\Plugin\DataType;

/**
 * @DataType(
 *   id = "xero_tax_rate",
 *   label = @Translation("Xero Tax Rate"),
 *   definition_class = "\Drupal\xero\TypedData\Definition\TaxRateDefinition",
 *   list_class = "\Drupal\xero\Plugin\DataType\XeroItemList"
 * )
 */
class TaxRate extends XeroTypeBase {
  static public $guid_name;
  static public $xero_name = 'TaxRate';
  static public $plural_name = 'TaxRates';
  static public $label = 'Name';
}