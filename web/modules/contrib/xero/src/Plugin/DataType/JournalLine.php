<?php

namespace Drupal\xero\Plugin\DataType;

use Drupal\Core\TypedData\Plugin\DataType\Map;
use Drupal\xero\TypedData\XeroTypeInterface;

/**
 * Xero journal line item type
 *
 * @DataType(
 *   id = "xero_journal_line",
 *   label = @Translation("Xero Journal Line"),
 *   definition_class = "\Drupal\xero\TypedData\Definition\JournalLineDefinition",
 *   list_class = "\Drupal\xero\Plugin\DataType\XeroItemList"
 * )
 */
class JournalLine extends Map implements XeroTypeInterface {

  static public $xero_name = 'JournalLine';
  static public $plural_name = 'JournalLines';

  /**
   * {@inheritdoc}
   */
  public function getGUIDName() {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function getLabelName() {
    return 'JournalLineID';
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
      return '';
    }
    elseif ($name === 'label_name') {
      return 'JournalLineID';
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
