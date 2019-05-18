<?php

namespace Drupal\scheduling\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\MapItem;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Defines the 'scheduling_value' entity field type.
 *
 * @FieldType(
 *   id = "scheduling_value",
 *   label = @Translation("Scheduling value"),
 *   description = @Translation("A field for scheduling purposes."),
 *   list_class = "\Drupal\Core\Field\MapFieldItemList",
 *   default_widget = "scheduling",
 *   no_ui = TRUE,
 * )
 */
class SchedulingValueItem extends MapItem {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    // This is called very early by the user entity roles field. Prevent
    // early t() calls by using the TranslatableMarkup.
    $properties['value'] = DataDefinition::create('any')
      ->setLabel(new TranslatableMarkup('Items'));

    return $properties;
  }

}
