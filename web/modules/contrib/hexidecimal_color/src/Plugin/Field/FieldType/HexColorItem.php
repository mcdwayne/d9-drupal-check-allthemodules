<?php

namespace Drupal\hexidecimal_color\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Provides the (Hexidecimal) Color Field API field.
 *
 * @FieldType(
 *   id = "hexidecimal_color",
 *   label = @Translation("Color"),
 *   default_formatter = "hexidecimal_color_color_display",
 *   default_widget = "hexidecimal_color_widget",
 * )
 */
class HexColorItem extends FieldItemBase implements FieldItemInterface {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'color' => [
          'type' => 'varchar',
          'length' => 7,
          'not null' => FALSE,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('color')->getValue();

    return $value === NULL || $value === '' || $value === FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {

    $properties['color'] = DataDefinition::create('hexidecimal_color')
      ->setLabel(t('Hexidecimal color'));

    return $properties;
  }

}
