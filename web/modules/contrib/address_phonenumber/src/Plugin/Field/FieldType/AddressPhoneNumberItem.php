<?php

namespace Drupal\address_phonenumber\Plugin\Field\FieldType;

use Drupal\address\Plugin\Field\FieldType\AddressItem;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Class AddressPhoneNumberItem.
 *
 * @FieldType(
 *  id = "address_phone_number_item",
 *  label = @Translation("Address with Phonenumber"),
 *  description = @Translation("This entity for extending contact field in address module"),
 *  default_widget = "address_phone_number_default",
 *  default_formatter = "address_phone_number_default",
 * )
 */
class AddressPhoneNumberItem extends AddressItem {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema = parent::schema($field_definition);
    $schema['columns']['address_phonenumber'] = [
      'type' => 'varchar',
      'length' => 255,

    ];
    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = parent::propertyDefinitions($field_definition);
    $contact_definition = DataDefinition::create('string')
      ->setLabel(t('Address Phonenumber'));
    $properties['address_phonenumber'] = $contact_definition;
    return $properties;
  }

}
