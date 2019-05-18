<?php

namespace Drupal\xero\Plugin\DataType;

/**
 * Xero Item type.
 *
 * @DataType(
 *   id = "xero_item",
 *   label = @Translation("Xero Item"),
 *   definition_class = "\Drupal\xero\TypedData\Definition\ItemDefinition",
 *   list_class = "\Drupal\xero\Plugin\DataType\XeroItemList"
 * )
 */
class Item extends XeroTypeBase {

  static public $guid_name = 'Code';
  static public $xero_name = 'Item';
  static public $plural_name = 'Items';
  static public $label = 'Code';

}
