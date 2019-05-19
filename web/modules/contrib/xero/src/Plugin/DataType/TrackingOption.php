<?php

namespace Drupal\xero\Plugin\DataType;

use Drupal\Core\TypedData\Plugin\DataType\Map;
use Drupal\xero\TypedData\XeroTypeInterface;

/**
 * Xero Tracking Option type, as defined at
 * https://developer.xero.com/documentation/api/banktransactions
 *
 * @DataType(
 *   id = "xero_tracking_option",
 *   label = @Translation("Xero Tracking Option"),
 *   definition_class = "\Drupal\xero\TypedData\Definition\TrackingOptionDefinition",
 *   list_class = "\Drupal\xero\Plugin\DataType\XeroItemList"
 * )
 */
class TrackingOption extends Map implements XeroTypeInterface {

  /**
   * {@inheritdoc}
   */
  public function getGUIDName() {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function getPluralName() {
    // This will probably not work. The documentation suggests that a maximum of
    // 2 tracking options are allowed here, but Xero does not seem to accept
    // or store more than 1. Let's hope you're not trying to do this...
    return 'TrackingCategories';
  }

  /**
   * {@inheritdoc}
   */
  public function getLabelName() {
    return 'Option';
  }

  /**
   * {@inheritdoc}
   */
  public static function getXeroProperty($name) {
    if ($name === 'plural_name') {
      return 'TrackingCategories';
    }
    elseif ($name === 'guid_name') {
      return '';
    }
    elseif ($name === 'xero_name') {
      return 'TrackingCategory';
    }
    elseif ($name === 'label_name') {
      return 'Option';
    }

    throw new \InvalidArgumentException('Invalid xero property.');
  }

  /**
   * {@inheritdoc}
   */
  public function view() {
    $option = $this->getOption();
    $name = $this->getNameValue();

    return [
      '#markup' => $option . ': ' . $name,
    ];
  }

  protected function getOption() {
    $option = $this->get('Option');
    return $option->getValue() ? $option->getValue() : 'N/A';
  }

  protected function getNameValue() {
    $name = $this->get('Name');
    return $name->getValue() ? $name->getValue() : 'N/A';
  }
}
