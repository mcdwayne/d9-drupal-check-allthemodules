<?php

namespace Drupal\xero\Plugin\DataType;

/**
 * @DataType(
 *   id = "xero_repeating_invoice",
 *   label = @Translation("Xero Repeating Invoice"),
 *   data_definition_class = "\Drupal\xero\TypedData\Definition\RepeatingInvoice",
 *   list_class = "\Drupal\xero\Plugin\DataType\XeroItemList"
 * )
 */
class RepeatingInvoice extends Invoice {
  static public $guid_name = "RepeatingInvoiceID";
  static public $xero_name = 'RepeatingInvoice';
  static public $plural_name = 'RepeatingInvoices';
  static public $label = 'RepeatingInvoiceID';
}