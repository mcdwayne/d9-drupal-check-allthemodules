<?php

namespace Drupal\colorapi\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemInterface;

/**
 * Interface for Field API Color items.
 */
interface ColorItemInterface extends FieldItemInterface {

  /**
   * Set the human-readable 'name' proprty of the Color Field item.
   *
   * @param string $name
   *   The human-readable name of the color. Example "Red" or "Blue".
   * @param bool $notify
   *   Whether to notify the parent object of the change. Defaults to TRUE. If a
   *   property is updated from a parent object, set it to FALSE to avoid being
   *   notified again.
   */
  public function setName($name, $notify = TRUE);

  /**
   * Retrieve the human-readable name of the color.
   *
   * @return string
   *   The human-readable name of the color.
   */
  public function getColorName();

  /**
   * Set the human-readable 'name' proprty of the Color Field item.
   *
   * @param array $color
   *   An array of values to be set for the color. Values will be set based on
   *   the keys:
   *      - hexadecimal: The hexadecimal color string representing the color.
   *      - rgb: An array containing the following values:
   *         - red: The value for the RGB "red". An integer between 0 and 255.
   *         - green: The value for the RGB "green". An integer between 0 and
   *           255.
   *         - blue: The value for the RGB "blue". An integer between 0 and 255.
   * @param bool $notify
   *   Whether to notify the parent object of the change. Defaults to TRUE. If a
   *   property is updated from a parent object, set it to FALSE to avoid being
   *   notified again.
   */
  public function setColor(array $color, $notify = TRUE);

  /**
   * Retrieve the color array for the Color Field item.
   *
   * @return array
   *   An array containing the following keys:
   *      - hexadecimal: The hexadecimal string representation of the color.
   *      - rgb: An array containing the following values:
   *         - red: The value for the RGB "red". An integer between 0 and 255.
   *         - green: The value for the RGB "green". An integer between 0 and
   *           255.
   *         - blue: The value for the RGB "blue". An integer between 0 and 255.
   */
  public function getColor();

  /**
   * Set the 'hexadecimal' property of the Color Field 'Color' property.
   *
   * @param string $color
   *   The hexadecimal string value representing the color.
   * @param bool $notify
   *   Whether to notify the parent object of the change. Defaults to TRUE. If a
   *   property is updated from a parent object, set it to FALSE to avoid being
   *   notified again.
   */
  public function setHexadecimal($color, $notify = TRUE);

  /**
   * Retrieve the hexadecimal color string representation of the color.
   *
   * @return string
   *   The hexadecimal string representation of the color.
   */
  public function getHexadecimal();

  /**
   * Set the 'rgb' property of the Color Field 'Color' property.
   *
   * @param array $rgb
   *   An array containing the following values:
   *      - red: The value for the RGB "red". An integer between 0 and 255.
   *      - green: The value for the RGB "green". An integer between 0 and
   *        255.
   *      - blue: The value for the RGB "blue". An integer between 0 and 255.
   * @param bool $notify
   *   Whether to notify the parent object of the change. Defaults to TRUE. If a
   *   property is updated from a parent object, set it to FALSE to avoid being
   *   notified again.
   */
  public function setRgb(array $rgb, $notify = TRUE);

  /**
   * Retrieve RGB value array of the  Color Field Color data object.
   *
   * @return array
   *   An array containing the following keys:
   *      - red: The value for the RGB "red". An integer between 0 and 255.
   *      - green: The value for the RGB "green". An integer between 0 and
   *        255.
   *      - blue: The value for the RGB "blue". An integer between 0 and 255.
   */
  public function getRgb();

  /**
   * Set 'red' property of the Color Field 'Color' property's 'RGB' property.
   *
   * @param string $red
   *   The value for the RGB "red" property. An integer between 0 and 255.
   * @param bool $notify
   *   Whether to notify the parent object of the change. Defaults to TRUE. If a
   *   property is updated from a parent object, set it to FALSE to avoid being
   *   notified again.
   */
  public function setRed($red, $notify = TRUE);

  /**
   * Retrieve 'Red' value of the Color Field Color data object RGB property.
   *
   * @return array
   *   The value for the RGB "red". An integer between 0 and 255.
   */
  public function getRed();

  /**
   * Set 'green' property of the Color Field 'Color' property's 'RGB' property.
   *
   * @param string $green
   *   The value for the RGB "green" property. An integer between 0 and 255.
   * @param bool $notify
   *   Whether to notify the parent object of the change. Defaults to TRUE. If a
   *   property is updated from a parent object, set it to FALSE to avoid being
   *   notified again.
   */
  public function setGreen($green, $notify = TRUE);

  /**
   * Retrieve 'Green' value of the Color Field Color data object RGB property.
   *
   * @return array
   *   The value for the RGB "red". An integer between 0 and 255.
   */
  public function getGreen();

  /**
   * Set 'blue' property of the Color Field 'Color' property's 'RGB' property.
   *
   * @param string $blue
   *   The value for the RGB "blue" property. An integer between 0 and 255.
   * @param bool $notify
   *   Whether to notify the parent object of the change. Defaults to TRUE. If a
   *   property is updated from a parent object, set it to FALSE to avoid being
   *   notified again.
   */
  public function setBlue($blue, $notify = TRUE);

  /**
   * Retrieve 'Blue' value of the Color Field Color data object RGB property.
   *
   * @return array
   *   The value for the RGB "red". An integer between 0 and 255.
   */
  public function getBlue();

}
