<?php

namespace Drupal\migrate_override\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\MapItem;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'migrate_override_field_item' field type.
 *
 * @FieldType(
 *   id = "migrate_override_field_item",
 *   label = @Translation("Migrate Overide data field"),
 *   description = @Translation("A field to hold migrate override data"),
 *   category = @Translation("Migrate"),
 *   default_widget = "override_widget_default",
 *   default_formatter = "override_formatter_default"
 * )
 */
class MigrateOverrideFieldItem extends MapItem {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('string')
      ->setLabel(t('Serialized values'));

    return $properties;
  }

}
