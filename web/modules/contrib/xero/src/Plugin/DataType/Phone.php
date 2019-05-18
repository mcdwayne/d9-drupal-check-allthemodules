<?php

namespace Drupal\xero\Plugin\DataType;

use Drupal\Core\TypedData\Exception\MissingDataException;
use Drupal\Core\TypedData\Plugin\DataType\Map;
use Drupal\xero\TypedData\XeroTypeInterface;

/**
 * Xero phone type
 *
 * @DataType(
 *   id = "xero_phone",
 *   label = @Translation("Xero Phone"),
 *   definition_class = "\Drupal\xero\TypedData\Definition\PhoneDefinition",
 *   list_class = "\Drupal\xero\Plugin\DataType\XeroItemList"
 * )
 */
class Phone extends Map implements XeroTypeInterface {

  static public $xero_name = 'Phone';
  static public $plural_name = 'Phones';

  /**
   * Get the canonical phone number.
   *
   * @return string
   *   The full phone number.
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public function getPhone() {
    return $this->get('PhoneCountryCode')->getValue() . '-' . $this->get('PhoneAreaCode')->getValue() . '-' . $this->get('PhoneNumber')->getValue();
  }

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
    return 'PhoneNumber';
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
      return 'PhoneNumber';
    }

    throw new \InvalidArgumentException('Invalid xero property.');
  }

  /**
   * {@inheritdoc}
   */
  public function view() {
    try {
      $phone = $this->getPhone();
    }
    catch (MissingDataException $e) {
      $phone = '';
    }

    return [
      '#type' => 'html_tag',
      '#tag' => 'span',
      '#value' => $phone,
    ];
  }
}
