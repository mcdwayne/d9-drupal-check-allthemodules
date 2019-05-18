<?php

/**
 * @file
 * FruitFactory class.
 */

namespace Drupal\oop_example_13\BusinessLogic\Fruit;
use Drupal\oop_example_13\BusinessLogic\Common\ColorableFactoryInterface;

/**
 * FruitFactory class.
 */
class FruitFactory implements ColorableFactoryInterface {

  /**
   * Returns object which supports ColorInterface.
   */
  public function getColorable($class_name, $color_name = NULL) {

    $obj = new Fruit();

    switch ($class_name) {
      case 'Banana':
        $obj = new Banana();
        break;

      case 'Orange':
        $obj = new Orange();
        break;

      default:
        // Do nothing.
        break;
    }

    if (isset($color_name)) {
      $obj->color = $color_name;
    }

    return $obj;
  }

}
