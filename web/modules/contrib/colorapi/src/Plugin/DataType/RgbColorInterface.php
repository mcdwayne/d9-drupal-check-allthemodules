<?php

namespace Drupal\colorapi\Plugin\DataType;

use Drupal\Core\TypedData\ComplexDataInterface;

/**
 * Interface for the Typed Data RGB Color Complex Data type.
 */
interface RgbColorInterface extends ComplexDataInterface {

  /**
   * Set 'red' property of the RGB object.
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
   * Retrieve 'Red' value of the RGB data object.
   *
   * @return array
   *   The value for "red" value. An integer between 0 and 255.
   */
  public function getRed();

  /**
   * Set 'green' property of the RGB object.
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
   * Retrieve 'Green' value of the RGB data object.
   *
   * @return array
   *   The value for "green" value. An integer between 0 and 255.
   */
  public function getGreen();

  /**
   * Set 'blue' property of the RGB object.
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
   * Retrieve 'blue' value of the RGB data object.
   *
   * @return array
   *   The value for "blue" value. An integer between 0 and 255.
   */
  public function getBlue();

}
