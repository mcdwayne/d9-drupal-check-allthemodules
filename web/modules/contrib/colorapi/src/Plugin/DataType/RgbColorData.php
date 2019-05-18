<?php

namespace Drupal\colorapi\Plugin\DataType;

use Drupal\Core\TypedData\Plugin\DataType\Map;

/**
 * Provides the RGB Color Typed Data type.
 *
 * This data type is a wrapper for RGB colors. It holds the value in RGB format,
 * where red, blue and green each have a value between 0 and 255.
 *
 * @DataType(
 *   id = "rgb_color",
 *   label = @Translation("RGB Color"),
 *   description = @Translation("A Complex Data object containing a color in RGB format, with fields for Red, Green and Blue, each containing a value between 0 and 255."),
 *   definition_class = "\Drupal\colorapi\TypedData\Definition\RgbColorDefinition"
 * )
 */
class RgbColorData extends Map implements RgbColorInterface {

  /**
   * {@inheritdoc}
   */
  public function setValue($values, $notify = TRUE) {
    $this->setRed($values['red'], $notify);
    $this->setGreen($values['green'], $notify);
    $this->setBlue($values['blue'], $notify);
  }

  /**
   * {@inheritdoc}
   */
  public function setRed($red, $notify = TRUE) {
    $this->get('red')->setValue($red, $notify);
  }

  /**
   * {@inheritdoc}
   */
  public function getRed() {
    return $this->get('red')->getValue();
  }

  /**
   * {@inheritdoc}
   */
  public function setGreen($green, $notify = TRUE) {
    $this->get('green')->setValue($green, $notify);
  }

  /**
   * {@inheritdoc}
   */
  public function getGreen() {
    return $this->get('green')->getValue();
  }

  /**
   * {@inheritdoc}
   */
  public function setBlue($blue, $notify = TRUE) {
    $this->get('blue')->setValue($blue, $notify);
  }

  /**
   * {@inheritdoc}
   */
  public function getBlue() {
    return $this->get('blue')->getValue();
  }

}
