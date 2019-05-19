<?php

namespace Drupal\speakerdeck_field\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Provides a field type of baz.
 *
 * @FieldType(
 *   id = "speakerdeck_field",
 *   label = @Translation("SpeakerDeck"),
 *   default_formatter = "speakerdeck_formatter",
 *   default_widget = "speakerdeck_widget",
 * )
 */
class SpeakerDeckItem extends FieldItemBase implements FieldItemInterface {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return array(
      'columns' => array(
        'data_id' => array(
          'type' => 'varchar',
          'length' => '255',
          'not null' => FALSE,
        ),
        'data_ratio' => array(
          'type' => 'varchar',
          'length' => '255',
          'not null' => FALSE,
        ),
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['data_id'] = DataDefinition::create('string');
    $properties['data_ratio'] = DataDefinition::create('string');

    return $properties;
  }

}
