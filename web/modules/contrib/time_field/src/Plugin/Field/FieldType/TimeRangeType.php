<?php

namespace Drupal\time_field\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'time' field type.
 *
 * @FieldType(
 *   category= @Translation("General"),
 *   id = "time_range",
 *   label = @Translation("Time Range"),
 *   description = @Translation("Time range field"),
 *   default_widget = "time_range_widget",
 *   default_formatter = "time_range_formatter"
 * )
 */
class TimeRangeType extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    // Prevent early t() calls by using the TranslatableMarkup.
    $properties['from'] = DataDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('Seconds passed through midnight'))
      ->setSetting('unsigned', TRUE)
      ->setSetting('size', 'small')
      ->setRequired(TRUE);

    $properties['to'] = DataDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('Seconds passed through midnight'))
      ->setSetting('unsigned', TRUE)
      ->setSetting('size', 'small')
      ->setRequired(TRUE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema = [
      'columns' => [
        'from' => [
          'type' => 'int',
          'length' => 6,
        ],
        'to' => [
          'type' => 'int',
          'length' => 6,
        ],
      ],
      'indexes' => [
        'value' => ['from', 'to'],
      ],
    ];

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $from = $this->get('from')->getValue();
    $to = $this->get('to')->getValue();
    return trim($from) === '' || trim($to) === '';
  }

}
