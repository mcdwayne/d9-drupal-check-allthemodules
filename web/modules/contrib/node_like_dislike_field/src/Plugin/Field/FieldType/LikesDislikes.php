<?php

namespace Drupal\node_like_dislike_field\Plugin\Field\FieldType;

/**
 * @file
 * Contains \Drupal\node_like_dislike_field\Plugin\Field\FieldType\LikesDislikes.
 */
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'likes_dislikes' field type.
 *
 * @FieldType(
 *   id = "likes_dislikes",
 *   label = @Translation("Likes Dislikes"),
 *   description = @Translation("This field counts number of likes and dislikes on a node page"),
 *   default_widget = "likes_dislikes_default_widget",
 *   default_formatter = "likes_dislikes_default_formatter"
 * )
 */
class LikesDislikes extends FieldItemBase {

  /**
   * Overrides propertyDefinitions function of FieldItemBase class.
   *
   * @param Drupal\Core\Field\FieldStorageDefinitionInterface $field_definition
   *   Returns the field definition.
   *
   * @return properties
   *   A render array with flagplus banners (if any applicable).
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {

    $properties = [];
    $properties['likes'] = DataDefinition::create('string')
      ->setLabel(t('likes label'));
    $properties['dislikes'] = DataDefinition::create('string')
      ->setLabel(t('dislikes label'));
    $properties['clicked_by'] = DataDefinition::create('string')
      ->setLabel(t('clicked by label'));
    $properties['date'] = DataDefinition::create('string')
      ->setLabel(t('date label'));
    return $properties;
  }

  /**
   * Overrides schema function of FieldItemBase class.
   *
   * @param Drupal\Core\Field\FieldStorageDefinitionInterface $field_definition
   *   Returns the field definition.
   *
   * @return array
   *   A render array with flagplus banners (if any applicable).
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {

    $schema = [
      'columns' => [
        'likes' => [
          'type' => 'int',
          'default' => 0,
        ],
        'dislikes' => [
          'type' => 'int',
          'default' => 0,
        ],
        'clicked_by' => [
          'type' => 'blob',
          'size' => 'big',
          'not null' => FALSE,
        ],
        'date' => [
          'type' => 'varchar',
          'length' => 256,
          'not null' => FALSE,
        ],
      ],
    ];
    return $schema;
  }

}
