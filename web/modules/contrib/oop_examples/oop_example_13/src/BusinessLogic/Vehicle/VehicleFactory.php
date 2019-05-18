<?php

/**
 * @file
 * VehicleFactory class.
 */

namespace Drupal\oop_example_13\BusinessLogic\Vehicle;

use Drupal\oop_example_13\BusinessLogic\Common\ColorableFactoryInterface;
use Drupal\oop_example_13\BusinessLogic\Vehicle\Car\Toyota\ToyotaCamry;
use Drupal\oop_example_13\BusinessLogic\Vehicle\Car\Toyota\ToyotaYaris;

/**
 * VehicleFactory class.
 */
class VehicleFactory implements ColorableFactoryInterface {

  /**
   * Returns object which supports ColorInterface.
   */
  public function getColorable($class_name, $color_name = NULL) {

    $obj = new Vehicle();

    switch ($class_name) {
      case 'Toyota Camry':
        $obj = new ToyotaCamry();
        break;

      case 'Toyota Yaris':
        $obj = new ToyotaYaris();
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
