<?php

/**
 * @file
 * Fruit class.
 */

namespace Drupal\oop_example_13\BusinessLogic\Fruit;

use Drupal\oop_example_13\BusinessLogic\Common\ColorInterface;

/**
 * Fruit class.
 */
class Fruit implements ColorInterface {

  /**
   * The fruit color.
   *
   * @var string
   *
   * Default color translation t() is set up in class constructor because
   * expression is not allowed as field default value like:
   * public $color = t('green');
   */
  public $color;

  /**
   * Class constructor.
   */
  public function __construct() {
    $this->color = t('green');
  }

  /**
   * Implements ColorInterface.
   */
  public function getColor() {
    return $this->color;
  }

}
