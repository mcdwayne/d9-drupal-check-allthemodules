<?php

namespace Drupal\spatialfields\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'spatialfields geometry' field type.
 *
 * @FieldType(
 *   id = "spatialfields_geom",
 *   label = @Translation("Geometry"),
 *   category = @Translation("Spatial"),
 *   module = "spatialfields",
 *   description = @Translation("Creates geometry field"),
 *   default_widget = "spatialwidget",
 *   default_formatter = "spatialformatter"
 * )
 */


class SpatialFieldsGeom extends FieldItemBase {
    
    
  public $geometries = ['point' => 'Point', 'line' => 'Line', 'polygon' => 'Polygon'];
 /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return array(
      'columns' => array(
        'value' => array(
          'type' => 'text',
          'size' => 'tiny',
          'not null' => FALSE,
        ),
      ),
    );
  }
  
  /**
  * {@inheritdoc}
  */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = [];
    $properties['value'] = DataDefinition::create('string');
    return $properties;
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
public static function defaultFieldSettings() {
  return [
    // Declare a single setting, 'size', with a default
    // value of 'large'
    'type' => 'point',
  ] + parent::defaultFieldSettings();
}
  


 /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    // Get base form from FileItem.
    $element = parent::fieldSettingsForm($form, $form_state);

    $settings = $this->getSettings();
    $element['type'] = [
      '#type' => 'radios',
      '#title' => t('Type'),
      '#options' => $this->geometries,
      '#default_value' => $settings['type'],
      '#description' => t('The title attribute is used as a tooltip when the mouse hovers over the image. Enabling this field is not recommended as it can cause problems with screen readers.'),
    ];
    return $element;
  }



}