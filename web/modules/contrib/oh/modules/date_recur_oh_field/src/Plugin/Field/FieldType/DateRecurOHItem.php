<?php

namespace Drupal\date_recur_oh_field\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\date_recur\Plugin\Field\FieldType\DateRecurItem;

/**
 * Extends Date Recur field adding opening-hours features.
 *
 * @FieldType(
 *   id = "date_recur_oh",
 *   label = @Translation("Date Recur (Opening Hours)"),
 *   description = @Translation("Date recur field with opening hours."),
 *   default_widget = "date_recur_default_widget",
 *   default_formatter = "date_recur_default_formatter",
 *   list_class = "\Drupal\date_recur\Plugin\Field\FieldType\DateRecurFieldItemList"
 * )
 */
class DateRecurOHItem extends DateRecurItem {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition): array {
    $properties = parent::propertyDefinitions($field_definition);

    $properties['open'] = DataDefinition::create('boolean')
      ->setLabel(new TranslatableMarkup('Open'))
      ->setRequired(FALSE);

    $properties['message'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Message'))
      ->setRequired(FALSE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition): array {
    $schema = parent::schema($field_definition);

    $schema['columns']['open'] = [
      'description' => 'Consider as open',
      'type' => 'int',
      'size' => 'tiny',
    ];
    $schema['columns']['message'] = [
      'description' => 'Message',
      'type' => 'varchar',
      'length' => 255,
    ];

    return $schema;
  }

}
