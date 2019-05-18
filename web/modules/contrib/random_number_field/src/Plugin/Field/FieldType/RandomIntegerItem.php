<?php

namespace Drupal\random_number_field\Plugin\Field\FieldType;

use Drupal\Core\Field\Plugin\Field\FieldType\IntegerItem;

/**
 * Defines the 'integer' field type.
 *
 * @FieldType(
 *   id = "random_integer",
 *   label = @Translation("Random Number (integer)"),
 *   description = @Translation("This field stores a number in the database as an integer - ensure the default value is blank when saving."),
 *   category = @Translation("Number"),
 *   default_widget = "random_number",
 *   default_formatter = "random_number_integer"
 * )
 */
class RandomIntegerItem extends IntegerItem {

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return [
      'min' => '1',
      'max' => '10',
    ] + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function applyDefaultValue($notify = TRUE) {
    parent::applyDefaultValue($notify);

    // Random number fields default to a random number.
    $this
      ->setValue([
        'value' => mt_rand($this->getSetting('min'), $this->getSetting('max')),
      ], $notify);
    return $this;
  }

}
