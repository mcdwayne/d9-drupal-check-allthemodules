<?php

namespace Drupal\around_media\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'around_media' field type.
 *
 * @FieldType(
 *   id = "around_media",
 *   label = @Translation("Around Media"),
 *   description = @Translation("Around Media embed."),
 *   category = @Translation("Media"),
 *   default_widget = "around_media",
 *   default_formatter = "around_media_embed"
 * )
 */
class AroundMediaItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['tour'] = DataDefinition::create('string')
      ->setLabel(t('Tour code'))
      ->setRequired(TRUE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema = [
      'columns' => [
        'tour' => [
          'description' => 'Around Media tour (project) code.',
          'type' => 'varchar',
          'length' => 255,
          'not null' => TRUE,
        ],
      ],
    ];

    return $schema;
  }

}
