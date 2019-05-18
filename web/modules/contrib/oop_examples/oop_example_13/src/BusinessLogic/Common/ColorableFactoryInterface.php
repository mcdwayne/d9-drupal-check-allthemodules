<?php

/**
 * @file
 * ColorableFactory interface.
 */

namespace Drupal\oop_example_13\BusinessLogic\Common;

/**
 * ColorableFactory interface.
 */
interface ColorableFactoryInterface {

  /**
   * Returns object which supports ColorInterface.
   */
  public function getColorable($class_name, $color_name = NULL);
}
