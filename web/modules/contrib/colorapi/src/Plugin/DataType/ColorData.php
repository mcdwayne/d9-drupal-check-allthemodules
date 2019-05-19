<?php

namespace Drupal\colorapi\Plugin\DataType;

use Drupal\Core\TypedData\Plugin\DataType\Map;

/**
 * Provides the Color Typed Data type.
 *
 * This data type is a wrapper for colors. It has the following properties:
 *   - hexadecimal: A Simple Data hexadecimal_color object holding the color in
 *     the format #XXXXXX, where X is a hexadecimal character (0-9 or a-f).
 *   - rgb: A complex data object with the properties:
 *     - red: an integer with a value between 0 and 255.
 *     - green: an integer with a value between 0 and 255.
 *     - blue: an integer with a value between 0 and 255.
 *
 * @DataType(
 *   id = "colorapi_color",
 *   label = @Translation("Color"),
 *   description = @Translation("A Complex Data object containing a color in hexadecimal and RGB formats"),
 *   definition_class = "\Drupal\colorapi\TypedData\Definition\ColorDataDefinition"
 * )
 */
class ColorData extends Map implements ColorInterface {

  /**
   * {@inheritdoc}
   */
  public function setValue($values, $notify = TRUE) {
    $this->setHexadecimal($values['hexadecimal'], $notify);
    $this->setRgb($values['rgb'], $notify);
  }

  /**
   * {@inheritdoc}
   */
  public function getValue() {
    return $this->getHexadecimal();
  }

  /**
   * {@inheritdoc}
   */
  public function setHexadecimal($color, $notify = TRUE) {
    $this->get('hexadecimal')->setValue($color, $notify);
  }

  /**
   * {@inheritdoc}
   */
  public function getHexadecimal() {
    return $this->get('hexadecimal')->getValue();
  }

  /**
   * {@inheritdoc}
   */
  public function setRgb(array $rgb, $notify = TRUE) {
    $this->get('rgb')->setValue($rgb, $notify);
  }

  /**
   * {@inheritdoc}
   */
  public function getRgb() {
    return $this->get('rgb')->getValue();
  }

  /**
   * {@inheritdoc}
   */
  public function setRed($red, $notify = TRUE) {
    $this->get('rgb')->setRed($red, $notify);
  }

  /**
   * {@inheritdoc}
   */
  public function getRed() {
    return $this->get('rgb')->getRed();
  }

  /**
   * {@inheritdoc}
   */
  public function setGreen($green, $notify = TRUE) {
    $this->get('rgb')->setGreen($green, $notify);
  }

  /**
   * {@inheritdoc}
   */
  public function getGreen() {
    return $this->get('rgb')->getGreen();
  }

  /**
   * {@inheritdoc}
   */
  public function setBlue($blue, $notify = TRUE) {
    $this->get('rgb')->setBlue($blue, $notify);
  }

  /**
   * {@inheritdoc}
   */
  public function getBlue() {
    return $this->get('rgb')->getBlue();
  }

}
