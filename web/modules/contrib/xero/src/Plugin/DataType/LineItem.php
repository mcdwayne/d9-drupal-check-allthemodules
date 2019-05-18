<?php

namespace Drupal\xero\Plugin\DataType;

use Drupal\Core\TypedData\Plugin\DataType\Map;
use Drupal\xero\TypedData\XeroTypeInterface;

/**
 * Xero line item type
 *
 * @DataType(
 *   id = "xero_line_item",
 *   label = @Translation("Xero Line Item"),
 *   definition_class = "\Drupal\xero\TypedData\Definition\LineItemDefinition",
 *   list_class = "\Drupal\xero\Plugin\DataType\XeroItemList"
 * )
 */
class LineItem extends Map implements XeroTypeInterface {

  static public $xero_name = 'LineItem';
  static public $plural_name = 'LineItems';

  /**
   * {@inheritdoc}
   */
  public function getGUIDName() {
    return 'LineItemID';
  }

  /**
   * {@inheritdoc}
   */
  public function getLabelName() {
    return 'Description';
  }

  /**
   * {@inheritdoc}
   */
  public function getPluralName() {
    return self::$plural_name;
  }

  /**
   * {@inheritdoc}
   */
  public static function getXeroProperty($name) {
    if ($name === 'xero_name') {
      return self::$xero_name;
    }
    elseif ($name === 'plural_name') {
      return self::$plural_name;
    }
    elseif ($name === 'guid_name') {
      return 'LineItemID';
    }
    elseif ($name === 'label_name') {
      return 'Description';
    }

    throw new \InvalidArgumentException('Invalid xero property.');
  }

  /**
   * {@inheritdoc}
   */
  public function view() {
    // Expectation is that line items are rendered by their parent.
    return [];
  }

}
