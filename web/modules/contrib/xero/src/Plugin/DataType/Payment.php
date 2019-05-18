<?php

namespace Drupal\xero\Plugin\DataType;

/**
 * Xero Payment type.
 *
 * @DataType(
 *   id = "xero_payment",
 *   label = @Translation("Xero Payment"),
 *   definition_class = "\Drupal\xero\TypedData\Definition\PaymentDefinition",
 *   list_class = "\Drupal\xero\Plugin\DataType\XeroItemList"
 * )
 */
class Payment extends XeroTypeBase {

  static public $guid_name;
  static public $xero_name = 'Payment';
  static public $plural_name = 'Payments';
  static public $label = 'Reference';

}
