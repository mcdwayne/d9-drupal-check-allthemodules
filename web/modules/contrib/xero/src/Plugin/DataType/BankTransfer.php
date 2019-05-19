<?php

namespace Drupal\xero\Plugin\DataType;

/**
 * @DataType(
 *   id = "xero_bank_transfer",
 *   label = @Translation("Xero Bank Transfer"),
 *   definition_class = "\Drupal\xero\TypedData\Definition\BankTransferDefinition",
 *   list_class = "\Drupal\xero\Plugin\DataType\XeroItemList"
 * )
 */
class BankTransfer extends XeroTypeBase {

  static public $guid_name = 'BankTransferID';
  static public $xero_name = 'BankTransfer';
  static public $plural_name = 'BankTransfers';
  static public $label = 'Amount';

}