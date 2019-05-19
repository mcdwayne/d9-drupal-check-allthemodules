<?php

namespace Drupal\tripadvisor_integration\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\StringItemBase;

/**
 * Defines the 'tripadvisor_integration_tripadvisor_id' field type.
 *
 * @FieldType(
 *   id = "tripadvisor_integration_tripadvisor_id",
 *   label = @Translation("TripAdvisor ID"),
 *   description = @Translation("A TripAdvisor ID."),
 *   category = @Translation("TripAdvisor"),
 *   default_widget = "tripadvisor_integration_text",
 *   default_formatter = "tripadvisor_id_formatter",
 * )
 */
class TripAdvisorItem extends StringItemBase {

  /**
   * {@inheritdoc}
  */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema['columns']['value'] = [
      'type' => $field_definition->getSetting('is_ascii') === TRUE ? 'varchar_ascii' : 'varchar',
      'length' => 32,
    ];
    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    return parent::propertyDefinitions($field_definition);
  }

}
