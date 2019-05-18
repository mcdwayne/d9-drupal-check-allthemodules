<?php

/**
 * @file
 * Contains Drupal\hubspot_forms\Plugin\Field\FieldType\HubspotFormItem.
 */

namespace Drupal\hubspot_forms\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'field_hubspot_form' field type.
 *
 * @FieldType(
 *   id = "field_hubspot_form",
 *   label = @Translation("Hubspot Form"),
 *   module = "hubspot_forms",
 *   description = @Translation("Display Hubspot form."),
 *   default_widget = "field_hubspot_select",
 *   default_formatter = "field_hubspot_form_formatter",
 *   category = "Hubspot"
 * )
 */
class HubspotFormItem extends FieldItemBase {
  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'form_id' => [
          'type'     => 'varchar',
          'length'   => 255,
          'not null' => TRUE,
          'default'  => '',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('form_id')->getValue();
    return $value === NULL || $value === '';
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['form_id'] = DataDefinition::create('string')
      ->setLabel(t('Hubspot Form Data'));

    return $properties;
  }
}
