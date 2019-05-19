<?php

/**
 * @file
 * Contains \Drupal\viewmode_field\Plugin\Field\FieldType\ViewModeItem.
 */

namespace Drupal\viewmode_field\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'view_mode' field type.
 *
 * @FieldType(
 *   id = "view_mode",
 *   label = @Translation("View mode"),
 *   description = @Translation("Stores a View mode."),
 *   default_widget = "view_mode",
 *   default_formatter = "string",
 * )
 */
class ViewModeItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['view_mode'] = DataDefinition::create('string')
      ->setLabel(t('View mode'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return array(
      'columns' => array(
        'view_mode' => array(
          'type' => 'varchar',
          'description' => 'View mode.',
          'length' => 2048,
        ),
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getValue() {
    $view_mode = $this->get('view_mode');
    return $view_mode->getValue();
  }

}
