<?php

namespace Drupal\colorapi\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Intefrface for Color Entities.
 */
interface ColorInterface extends ConfigEntityInterface {

  /**
   * Retrieve the hexadecimal color string representation of the color.
   *
   * @return string
   *   The hexadecimal string representation of the color.
   */
  public function getHexadecimal();

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
   * Retrieve 'Red' value of the Color Field Color data object RGB property.
   *
   * @return array
   *   The value for the RGB "red". An integer between 0 and 255.
   */
  public function getRed();

  /**
   * Retrieve 'Green' value of the Color Field Color data object RGB property.
   *
   * @return array
   *   The value for the RGB "red". An integer between 0 and 255.
   */
  public function getGreen();

  /**
   * Retrieve 'Blue' value of the Color Field Color data object RGB property.
   *
   * @return array
   *   The value for the RGB "red". An integer between 0 and 255.
   */
  public function getBlue();

}
