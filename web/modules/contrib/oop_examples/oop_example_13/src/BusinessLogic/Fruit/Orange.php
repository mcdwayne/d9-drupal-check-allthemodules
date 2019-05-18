<?php

/**
 * @file
 * Orange class.
 */

namespace Drupal\oop_example_13\BusinessLogic\Fruit;

/**
 * Orange class.
 */
class Orange extends Fruit {

  /**
   * Class constructor.
   */
  public function __construct() {
    $this->color = t('orange');
  }

}
