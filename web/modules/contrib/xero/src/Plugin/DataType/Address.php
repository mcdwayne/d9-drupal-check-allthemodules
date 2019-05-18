<?php

namespace Drupal\xero\Plugin\DataType;

use Drupal\Component\Utility\Html;
use Drupal\Core\TypedData\Plugin\DataType\Map;
use Drupal\xero\TypedData\XeroTypeInterface;

/**
 * Xero address type
 *
 * @DataType(
 *   id = "xero_address",
 *   label = @Translation("Xero Address"),
 *   definition_class = "\Drupal\xero\TypedData\Definition\AddressDefinition",
 *   list_class = "\Drupal\xero\Plugin\DataType\XeroItemList"
 * )
 */
class Address extends Map implements XeroTypeInterface {

  static public $xero_name = 'Address';
  static public $plural_name = 'Addresses';

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
    return 'AddressLine1';
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
      return 'AddressLine1';
    }

    throw new \InvalidArgumentException('Invalid xero property.');
  }

  /**
   * {@inheritdoc}
   */
  public function view() {
    $values = $this->getValue();
    $props = [
      'AttentionTo',
      'AddressLine1',
      'AddressLine2',
      'AddressLine3',
      'AddressLine4',
      'City',
      'Region',
      'PostalCode',
      'Country',
    ];

    $ret = [
      '#type' => 'container',
      '#prefix' => '<address>',
      '#suffix' => '</address>',
    ];

    foreach ($props as $prop) {
      if (isset($values[$prop])) {
        $suffix = !in_array($prop, ['City', 'Region']) ? '<br>' : '';
        $ret[$prop] = [
          '#markup' => Html::escape($values[$prop]) . $suffix,
        ];
      }
    }

    return $ret;
  }

}
