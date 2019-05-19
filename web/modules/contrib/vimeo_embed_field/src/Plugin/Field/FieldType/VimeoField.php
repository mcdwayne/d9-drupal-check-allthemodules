<?php

namespace Drupal\vimeo_embed_field\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'vimeo' field type.
 *
 * @FieldType(
 *   id = "vimeo",
 *   label = @Translation("Vimeo embed field"),
 *   description = @Translation("This field stores a Vimeo video URL in the Drupal database."),
 *   default_widget = "vimeo",
 *   default_formatter = "vimeo"
 * )
 */
class VimeoField extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    return [
      'max_length' => 255,
    ] + parent::defaultStorageSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    // Prevent early t() calls by using the TranslatableMarkup.
    $properties['vimeo_url'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Vimeo Video URL'))
      ->setRequired(TRUE);
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'vimeo_url' => [
          'type' => 'varchar',
          'description' => 'Vimeo Video URL.',
          'length' => 256,
          'not null' => FALSE,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('vimeo_url')->getValue();
    return $value === NULL || $value === '';
  }

}
