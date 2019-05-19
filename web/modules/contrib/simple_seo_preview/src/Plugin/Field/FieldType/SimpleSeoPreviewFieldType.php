<?php

namespace Drupal\simple_seo_preview\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'simple_seo_preview' field type.
 *
 * @FieldType(
 *   id = "simple_seo_preview",
 *   label = @Translation("Simple SEO preview"),
 *   description = @Translation("Simple SEO preview field type"),
 *   default_widget = "simple_seo_preview_widget_type",
 *   default_formatter = "simple_seo_preview_empty_formatter",
 *   serialized_property_names = {
 *     "value"
 *   }
 * )
 */
class SimpleSeoPreviewFieldType extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('simple_seo_preview')
      ->setLabel(t('Simple SEO preview'))
      ->setRequired(TRUE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema = [
      'columns' => [
        'value' => [
          'type'     => 'text',
          'size'     => 'big',
          'not null' => FALSE,
        ],
      ],
    ];

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('value')->getValue();
    return $value === NULL || $value === '';
  }

}
