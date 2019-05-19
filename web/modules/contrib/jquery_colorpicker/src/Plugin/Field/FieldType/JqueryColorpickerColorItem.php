<?php

namespace Drupal\jquery_colorpicker\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Provides the Color field.
 *
 * @FieldType(
 *   id = "jquery_colorpicker",
 *   label = @Translation("Color"),
 *   default_formatter = "colorapi_color_display",
 *   default_widget = "colorapi_color_widget",
 * )
 *
 * @deprecated as of Jquery Colorpicker update 8200. Will be removed in Jquery
 *   Colorpicker 8.x-3.x, and/or 9.x-1.x. Running
 *   jquery_colorpicker_update_8200() requires the existence of this field type,
 *   however the field type is obsolete after that update has been run. As such,
 *   if the schema version is equal to or above 8200, the field type is removed
 *   from the list of field types in jquery_colorpicker_field_info_alter().
 */
class JqueryColorpickerColorItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'name' => [
          'type' => 'varchar',
          'length' => 255,
          'not null' => FALSE,
        ],
        'color' => [
          'type' => 'varchar',
          'length' => 7,
          'not null' => TRUE,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $typed_data_manager = \Drupal::typedDataManager();

    $properties['name'] = DataDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The human readable name of the color'));

    $color_definition_info = $typed_data_manager->getDefinition('colorapi_color');
    $properties['color'] = $color_definition_info['definition_class']::create('colorapi_color')
      ->setLabel(t('Color'))
      ->setDescription(t('The color, in hexadecimal and RGB format.'))
      ->setRequired(TRUE);

    return $properties;
  }

}
