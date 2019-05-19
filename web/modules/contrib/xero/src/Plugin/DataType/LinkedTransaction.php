<?php

namespace Drupal\xero\Plugin\DataType;

/**
 * @DataType(
 *   id = "xero_linked_transaction",
 *   label = @Translation("Xero Linked Transaction"),
 *   definition_class = "\Drupal\xero\TypedData\Definition\LinkedTransactionDefinition",
 *   list_class = "\Drupal\xero\Plugin\DataType\XeroItemList"
 * )
 */
class LinkedTransaction extends XeroTypeBase {
  static public $guid_name = 'LinkedTransactionID';
  static public $xero_name = 'LinkedTtransaction';
  static public $plural_name = 'LinkedTransactions';
  static public $label = 'SourceTransactionID';
}