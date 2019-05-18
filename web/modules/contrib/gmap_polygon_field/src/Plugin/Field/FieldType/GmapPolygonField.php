<?php

namespace Drupal\gmap_polygon_field\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Provides a field type of gmap polygon field.
 * 
 * @FieldType(
 *   id = "gmap_polygon_field",
 *   label = @Translation("GMap Polygon Field"),
 *   default_formatter = "gmap_polygon_field_basic_formatter",
 *   default_widget = "gmap_polygon_field_basic_widget",
 * )
 */


class GmapPolygonField extends FieldItemBase implements FieldItemInterface {
  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
  	return array(
  	  'columns' => array(
  	  	'polyline' => array(
  	  	  'type' => 'text',
  	  	  'not null' => FALSE,
  	  	  'size' => 'big',
  	  	),
  	  ),
  	);
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
  	$properties = array();
  	$properties['polyline'] = DataDefinition::create('string');
  	return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
  	return empty($this->get('polyline')->getValue());
  }
}
