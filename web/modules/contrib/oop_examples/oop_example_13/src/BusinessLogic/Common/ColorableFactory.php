<?php

/**
 * @file
 * ColorableFactory class.
 */

namespace Drupal\oop_example_13\BusinessLogic\Common;
use Drupal\oop_example_13\BusinessLogic\Fruit\Banana;
use Drupal\oop_example_13\BusinessLogic\Fruit\Fruit;
use Drupal\oop_example_13\BusinessLogic\Fruit\FruitFactory;
use Drupal\oop_example_13\BusinessLogic\Vehicle\Car\Toyota\ToyotaCamry;
use Drupal\oop_example_13\BusinessLogic\Vehicle\Car\Toyota\ToyotaYaris;
use Drupal\oop_example_13\BusinessLogic\Fruit\Orange;
use Drupal\oop_example_13\BusinessLogic\Vehicle\VehicleFactory;


/**
 * ColorableFactory class.
 *
 * Creates classes which support ColorInterface.
 */
class ColorableFactory implements ColorableFactoryInterface {

  /**
   * Returns object which supports ColorInterface.
   */
  public function getColorable($class_name, $color_name = NULL) {

    $obj = new Fruit();

    switch ($class_name) {
      case 'Toyota Camry':
      case 'Toyota Yaris':
        $vehicle_factory = new VehicleFactory();
        $obj = $vehicle_factory->getColorable($class_name, $color_name);
        break;

      case 'Banana':
      case 'Orange':
        $fruit_factory = new FruitFactory();
        $obj = $fruit_factory->getColorable($class_name, $color_name);
        break;

      default:
        // Do nothing.
        break;
    }

    return $obj;
  }

}
