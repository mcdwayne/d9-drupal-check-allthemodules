<?php

namespace Drupal\instagram_field\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'instagramfield' field type.
 *
 * @FieldType(
 *   id = "instagramfield",
 *   label = @Translation("Instagram Field"),
 *   category = @Translation("Media"),
 *   default_widget = "instagramfield_default",
 *   default_formatter = "instagramfield_formatter"
 * )
 */
class InstagramFieldItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'instagramid' => [
          'type' => 'varchar',
          'length' => 256,
        ],
        'instagramlink' => [
          'type' => 'varchar',
          'length' => 256,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['instagramid'] = DataDefinition::create('string')
      ->setLabel(t('Instagram user ID'))
      ->setRequired(TRUE);
    $properties['instagramlink'] = DataDefinition::create('string')
      ->setLabel(t('Instagram link'))
      ->setRequired(TRUE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    return FALSE;
  }

}
