<?php

namespace Drupal\xero\Plugin\DataType;

/**
 * @DataType(
 *   id = "xero_currency",
 *   label = @Translation("Xero Currency"),
 *   definition_class = "\Drupal\xero\TypedData\Definition\CurrencyDefinition",
 *   list_class = "\Drupal\xero\Plugin\DataType\XeroItemList"
 * )
 */
class Currency extends XeroTypeBase {
  static public $guid_name;
  static public $xero_name = 'Currency';
  static public $plural_name = 'Currencies';
  static public $label = 'Code';
}