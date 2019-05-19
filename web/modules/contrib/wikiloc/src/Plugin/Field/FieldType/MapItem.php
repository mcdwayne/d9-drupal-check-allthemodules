<?php

namespace Drupal\wikiloc\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'Wikiloc' field type.
 *
 * @FieldType(
 *   id = "wikiloc_map_field",
 *   label = @Translation("Wikiloc Map field"),
 *   description = @Translation("This field stores Wikiloc fields in the database."),
 *   default_widget = "wikiloc_map_field_default",
 *   default_formatter = "wikiloc_map_field_default"
 * )
 */
class MapItem extends FieldItemBase {
  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return array(
      'columns' => array(
        'id' => array(
          'type' => 'varchar',
          'length' => 15,
          'not null' => TRUE,
        ),
        'measures' => array(
          'type' => 'int',
          'length' => 1,
          'default' => 1
        ),
        'near' => array(
          'type' => 'int',
          'length' => 1,
          'default' => 1
        ),
        'images' => array(
          'type' => 'int',
          'length' => 1,
          'default' => 1
        ),
        'maptype' => array(
          'type' => 'varchar',
          'length' => 1,
          'not null' => FALSE,
          'default' => 'S',
        ),
        'width' => array(
          'type' => 'varchar',
          'length' => 10,
          'not null' => FALSE,
        ),
        'height' => array(
          'type' => 'varchar',
          'length' => 10,
          'not null' => FALSE,
        ),
        'metricunits' => array(
          'type' => 'int',
          'length' => 1,
          'default' => 1
        ),
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('id')->getValue();
    return $value === NULL || $value === '';
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['id'] = DataDefinition::create('string')
      ->setLabel(t('Id'));
    $properties['measures'] = DataDefinition::create('boolean')
      ->setLabel(t('Measures'));
    $properties['near'] = DataDefinition::create('boolean')
      ->setLabel(t('Near'));
    $properties['images'] = DataDefinition::create('boolean')
      ->setLabel(t('Images'));
    $properties['maptype'] = DataDefinition::create('string')
      ->setLabel(t('Type map'));
    $properties['width'] = DataDefinition::create('string')
      ->setLabel(t('Width'));
    $properties['height'] = DataDefinition::create('string')
      ->setLabel(t('Height'));
    $properties['metricunits'] = DataDefinition::create('boolean')
      ->setLabel(t('Metric Units'));

    return $properties;
  }

}
