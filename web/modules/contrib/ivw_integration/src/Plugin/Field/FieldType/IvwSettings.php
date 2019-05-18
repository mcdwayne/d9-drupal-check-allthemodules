<?php

namespace Drupal\ivw_integration\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'ivw_integration_settings' field type.
 *
 * @FieldType(
 *   id = "ivw_integration_settings",
 *   label = @Translation("IVW settings"),
 *   description = @Translation("Define content specific IVW settings. These settings override the default settings."),
 *   default_widget = "ivw_integration_widget",
 *   default_formatter = "ivw_empty_formatter"
 * )
 */
class IvwSettings extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'offering' => [
          'type' => 'varchar',
          'length' => 256,
          'not null' => TRUE,
        ],
        'language' => [
          'type' => 'varchar',
          'length' => 256,
          'not null' => TRUE,
        ],
        'frabo' => [
          'type' => 'varchar',
          'length' => 256,
        ],
        'frabo_mobile' => [
          'type' => 'varchar',
          'length' => 256,
        ],
        'format' => [
          'type' => 'varchar',
          'length' => 256,
          'not null' => TRUE,
        ],
        'creator' => [
          'type' => 'varchar',
          'length' => 256,
          'not null' => TRUE,
        ],
        'homepage' => [
          'type' => 'varchar',
          'length' => 256,
          'not null' => TRUE,
        ],
        'delivery' => [
          'type' => 'varchar',
          'length' => 256,
          'not null' => TRUE,
        ],
        'app' => [
          'type' => 'varchar',
          'length' => 256,
          'not null' => TRUE,
        ],
        'paid' => [
          'type' => 'varchar',
          'length' => 256,
          'not null' => TRUE,
        ],
        'content' => [
          'type' => 'varchar',
          'length' => 256,
          'not null' => TRUE,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['offering'] = DataDefinition::create('string')->setLabel(t('Offering'));
    $properties['language'] = DataDefinition::create('string')->setLabel(t('Language'));
    $properties['frabo'] = DataDefinition::create('string')->setLabel(t('Frabo control'));
    $properties['frabo_mobile'] = DataDefinition::create('string')->setLabel(t('Frabo control for mobile'));
    $properties['format'] = DataDefinition::create('string')->setLabel(t('Format'));
    $properties['creator'] = DataDefinition::create('string')->setLabel(t('Creator'));
    $properties['homepage'] = DataDefinition::create('string')->setLabel(t('Homepage'));
    $properties['delivery'] = DataDefinition::create('string')->setLabel(t('Delivery'));
    $properties['app'] = DataDefinition::create('string')->setLabel(t('App'));
    $properties['paid'] = DataDefinition::create('string')->setLabel(t('Paid'));
    $properties['content'] = DataDefinition::create('string')->setLabel(t('Content'));

    return $properties;
  }

}
