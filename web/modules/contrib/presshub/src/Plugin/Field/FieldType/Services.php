<?php

namespace Drupal\presshub\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'field_presshub_services' field type.
 *
 * @FieldType(
 *   id = "field_presshub_services",
 *   label = @Translation("Services"),
 *   module = "presshub",
 *   description = @Translation("Presshub Services."),
 *   default_widget = "field_presshub_services",
 *   default_formatter = "field_presshub_services_formatter",
 *   category = "Presshub"
 * )
 */
class Services extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'service_name' => [
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
    $value = $this->get('service_name')->getValue();
    return $value === NULL || $value === '';
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['service_name'] = DataDefinition::create('string')
      ->setLabel(t('Presshub Service'));

    return $properties;
  }
}
