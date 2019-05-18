<?php

/**
 * @file
 * Banana class.
 */

namespace Drupal\oop_example_13\BusinessLogic\Fruit;

/**
 * Banana class.
 */
class Banana extends Fruit {

  /**
   * Class constructor.
   */
  public function __construct() {
    $this->color = t('yellow');
  }

}
