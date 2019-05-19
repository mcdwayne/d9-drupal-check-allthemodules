<?php

namespace Drupal\sketchfab\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'sketchfab_field' field type.
 *
 * @FieldType(
 *   id = "sketchfab_field",
 *   label = @Translation("Embed Sketchfab"),
 *   description = @Translation("Embed 3D model in pages."),
 *   category = @Translation("Media"),
 *   default_widget = "sketchfab_widget",
 *   default_formatter = "sketchfab_format"
 * )
 */
class SketchfabItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return array(
      'columns' => array(
        'value' => array(
          'type' => 'text',
          'not null' => FALSE,
        ),
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('value')->getValue();
    return $value === NULL || $value === '';
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('string')
      ->setLabel(t('Target URL'));

    return $properties;
  }

}