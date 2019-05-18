<?php

namespace Drupal\presshub\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'field_apple_news_sections' field type.
 *
 * @FieldType(
 *   id = "field_apple_news_sections",
 *   label = @Translation("Apple News Sections"),
 *   module = "presshub",
 *   description = @Translation("Apple News Sections."),
 *   default_widget = "field_apple_news_sections",
 *   default_formatter = "field_apple_news_sections_formatter",
 *   category = "Presshub"
 * )
 */
class AppleNewsSections extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'section_id' => [
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
    $value = $this->get('section_id')->getValue();
    return $value === NULL || $value === '';
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['section_id'] = DataDefinition::create('string')
      ->setLabel(t('Apple News Section ID'));

    return $properties;
  }
}
