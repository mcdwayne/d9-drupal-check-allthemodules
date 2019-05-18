<?php

namespace Drupal\libphonenumber\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\libphonenumber\LibPhoneNumberInterface;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;

/**
 * Plugin implementation of the 'libphonenumber' field type.
 *
 * @FieldType(
 *   id = "libphonenumber",
 *   label = @Translation("Phone number"),
 *   description = @Translation("Phone number field based on libphonenumber-for-php."),
 *   default_widget = "libphonenumber",
 *   default_formatter = "libphonenumber_link"
 * )
 */
class LibPhoneNumberItem extends FieldItemBase implements LibPhoneNumberInterface {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'raw_input' => [
          'type' => 'varchar',
          'length' => 127,
        ],
        'country_code' => [
          'type' => 'int',
          'unsigned' => TRUE,
          'size' => 'small',
        ],
        'national_number' => [
          'type' => 'varchar',
          'length' => 32,
        ],
        'extension' => [
          'type' => 'varchar',
          'length' => 64,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    return [
      'raw_input' => DataDefinition::create('string')
        ->setLabel(new TranslatableMarkup('The phone number as entered by the user.')),
      'country_code' => DataDefinition::create('integer')
        ->setLabel(new TranslatableMarkup('The country code.')),
      'national_number' => DataDefinition::create('string')
        ->setLabel(new TranslatableMarkup('The national number.')),
      'extension' => DataDefinition::create('string')
        ->setLabel(new TranslatableMarkup('The extension.')),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $raw_input = $this->get('raw_input')->getValue();
    $raw_input_is_empty = $raw_input === NULL || $raw_input === '';
    $country_code = $this->get('country_code')->getValue();
    return $raw_input_is_empty && empty($country_code);
  }

  /**
   * {@inheritdoc}
   */
  public function getRawInput() {
    return $this->raw_input;
  }

  /**
   * {@inheritdoc}
   */
  public function getCountryCode() {
    return $this->country_code;
  }

  /**
   * {@inheritdoc}
   */
  public function getNationalNumber() {
    return $this->national_number;
  }

  /**
   * {@inheritdoc}
   */
  public function getExtension() {
    return $this->extension;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormattedNumber($format = PhoneNumberFormat::E164) {
    $util = PhoneNumberUtil::getInstance();
    // @todo Allow to set a default country other than Belgium.
    $number = $util->parseAndKeepRawInput($this->getRawInput(), 'BE');

    return $util->format($number, $format);
  }

}
