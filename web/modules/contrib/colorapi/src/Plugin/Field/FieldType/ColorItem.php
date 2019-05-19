<?php

namespace Drupal\colorapi\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Provides the Color field.
 *
 * @FieldType(
 *   id = "colorapi_color_field",
 *   label = @Translation("Color"),
 *   default_formatter = "colorapi_color_display",
 *   default_widget = "colorapi_color_widget",
 * )
 */
class ColorItem extends FieldItemBase implements ColorItemInterface {

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
          'not null' => FALSE,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $hex_value = $this->getHexadecimal();

    return $hex_value === NULL || $hex_value === '' || $hex_value === FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    // Retrieve the Typed Data Plugin Manager. This will be used to retrieve
    // the data definitions for the properties of this Field type.
    $typed_data_manager = \Drupal::typedDataManager();

    // The Plugin ID of the Typed Data data type the name property will store:
    $string_data_type = 'string';
    // Retrieve the data definition for String Simple Data types.
    $string_definition_info = $typed_data_manager->getDefinition($string_data_type);
    // Use the definition class for the data type to create a new String object
    // and set some values on it.
    $properties['name'] = $string_definition_info['definition_class']::create($string_data_type)
      ->setLabel(t('Name'))
      ->setDescription(t('The human readable name of the color'));

    // The Plugin ID of the Typed Data data type the color property will store:
    $color_data_type = 'colorapi_color';
    // Retrieve the data definition for Color complex Data types.
    $color_definition_info = $typed_data_manager->getDefinition($color_data_type);
    // Use the definition class for the data type to create a new Color object
    // and set some values on it.
    $properties['color'] = $color_definition_info['definition_class']::create($color_data_type)
      ->setLabel(t('Color'))
      ->setDescription(t('The color, in hexadecimal and RGB format.'))
      ->setRequired(TRUE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($values, $notify = TRUE) {
    $this->setName($values['name'], $notify);
    $color_string = isset($values['color']['hexadecimal']) ? $values['color']['hexadecimal'] : $values['color'];
    $this->setHexadecimal($color_string, $notify);
    $service = \Drupal::service('colorapi.service');
    $this->setRed($service->hexToRgb($color_string, 'red'), $notify);
    $this->setGreen($service->hexToRgb($color_string, 'green'), $notify);
    $this->setBlue($service->hexToRgb($color_string, 'blue'), $notify);
  }

  /**
   * {@inheritdoc}
   */
  public function setName($name, $notify = TRUE) {
    $this->get('name')->setValue($name, $notify);
  }

  /**
   * {@inheritdoc}
   */
  public function getColorName() {
    return $this->get('name')->getValue();
  }

  /**
   * {@inheritdoc}
   */
  public function setColor(array $color, $notify = TRUE) {
    $this->get('color')->setValue($color, $notify);
  }

  /**
   * {@inheritdoc}
   */
  public function getColor() {
    return $this->get('color')->getValue();
  }

  /**
   * {@inheritdoc}
   */
  public function setHexadecimal($color, $notify = TRUE) {
    $this->get('color')->setHexadecimal($color, $notify);
  }

  /**
   * {@inheritdoc}
   */
  public function getHexadecimal() {
    return $this->get('color')->getHexadecimal();
  }

  /**
   * {@inheritdoc}
   */
  public function setRgb(array $rgb, $notify = TRUE) {
    $this->get('color')->setRgb($rgb, $notify);
  }

  /**
   * {@inheritdoc}
   */
  public function getRgb() {
    return $this->get('color')->getRgb();
  }

  /**
   * {@inheritdoc}
   */
  public function setRed($red, $notify = TRUE) {
    $this->get('color')->setRed($red, $notify);
  }

  /**
   * {@inheritdoc}
   */
  public function getRed() {
    return $this->get('color')->getRed();
  }

  /**
   * {@inheritdoc}
   */
  public function setGreen($green, $notify = TRUE) {
    $this->get('color')->setGreen($green, $notify);
  }

  /**
   * {@inheritdoc}
   */
  public function getGreen() {
    return $this->get('color')->getGreen();
  }

  /**
   * {@inheritdoc}
   */
  public function setBlue($blue, $notify = TRUE) {
    $this->get('color')->setBlue($blue, $notify);
  }

  /**
   * {@inheritdoc}
   */
  public function getBlue() {
    return $this->get('color')->getBlue();
  }

}
