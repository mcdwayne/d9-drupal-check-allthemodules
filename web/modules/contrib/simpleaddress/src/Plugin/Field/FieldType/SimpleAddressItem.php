<?php

namespace Drupal\simpleaddress\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Component\Utility\Random;

/**
 * Plugin implementation of the 'simpleaddress' field type.
 *
 * @FieldType(
 *   id = "simpleaddress",
 *   label = @Translation("Simple Address"),
 *   description = @Translation("Stores a postal address."),
 *   default_widget = "simpleaddress_default",
 *   default_formatter = "simpleaddress_default"
 * )
 */
class SimpleAddressItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['streetAddress'] = DataDefinition::create('string')
      ->setLabel(t('Street address'));
    $properties['addressLocality'] = DataDefinition::create('string')
      ->setLabel(t('Town/City'));
    $properties['addressRegion'] = DataDefinition::create('string')
      ->setLabel(t('Region'));
    $properties['postalCode'] = DataDefinition::create('string')
      ->setLabel(t('Postal code'));
    $properties['postOfficeBoxNumber'] = DataDefinition::create('string')
      ->setLabel(t('P.O. Box'));
    $properties['addressCountry'] = DataDefinition::create('string')
      ->setLabel(t('Country'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return array(
      'columns' => array(
        'streetAddress' => array(
          'description' => 'The street address. For example, 102, Olive Grove.',
          'type' => 'varchar',
          'length' => 255,
          'not null' => FALSE,
        ),
        'addressLocality' => array(
          'description' => 'The town/city. For example, Swindon.',
          'type' => 'varchar',
          'length' => 255,
          'not null' => FALSE,
        ),
        'addressRegion' => array(
          'description' => 'The region. For example, Wiltshire',
          'type' => 'varchar',
          'length' => 255,
          'not null' => FALSE,
        ),
        'postalCode' => array(
          'description' => 'The postal code. For example, SN25 9RT.',
          'type' => 'varchar',
          'length' => 255,
          'not null' => FALSE,
        ),
        'postOfficeBoxNumber' => array(
          'description' => 'The post office box number for PO box addresses. For example, P.O. Box 12345',
          'type' => 'varchar',
          'length' => 255,
          'not null' => FALSE,
        ),
        'addressCountry' => array(
          'description' => 'The country. For example, USA. You can also provide the two-letter ISO 3166-1 alpha-2 country code.',
          'type' => 'varchar',
          'length' => 255,
          'not null' => TRUE,
        ),
      ),
    );
  }


  /**
   * {@inheritdoc}
   */
  public function preSave() {
    // Trim any spaces around the texts.
    $this->streetAddress = trim($this->streetAddress);
    $this->addressLocality = trim($this->addressLocality);
    $this->addressRegion = trim($this->addressRegion);
    $this->postalCode = trim($this->postalCode);
    $this->postOfficeBoxNumber = trim($this->postOfficeBoxNumber);
    $this->addressCountry = trim($this->addressCountry);
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('addressCountry')->getValue();
    return $value === NULL || $value === '';
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    $random = new Random();

    $countries = array_keys(\Drupal::service('country_manager')->getList());

    $streets = [
      'street',
      'avenue',
      'road',
      'crescent',
      'close',
      'way',
      'grove',
      'court',
      'garden'
    ];

    $values['streetAddress'] = mt_rand(12, 124) . ' ' . $random->word(mt_rand(5, 10)) . ' ' . $streets[mt_rand(0, (sizeof($streets) - 1))];
    $values['addressLocality'] = $random->word(mt_rand(4, 10));
    $values['addressRegion'] = $random->word(mt_rand(5, 8));
    $values['postalCode'] = $random->word(mt_rand(4, 6));
    if (mt_rand(0, 1)) {
      $values['postOfficeBoxNumber'] = mt_rand(227, 8539);
    }
    $values['addressCountry'] = $countries[mt_rand(0, (sizeof($countries) - 1))];

    return $values;
  }

}
