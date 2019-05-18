<?php

/**
 * @file
 * Vehicle class.
 */

namespace Drupal\oop_example_13\BusinessLogic\Vehicle;

use Drupal\oop_example_13\BusinessLogic\Common\ColorInterface;

/**
 * Vehicle class.
 */
class Vehicle implements ColorInterface {

  /**
   * The vehicle color.
   *
   * @var string
   *
   * Default color translation t() is set up in class constructor because
   * expression is not allowed as field default value like:
   * public $color = t('red');
   */
  public $color;

  /**
   * Class constructor.
   */
  public function __construct() {
    $this->color = t('red');
  }

  /**
   * Returns class type description.
   */
  public function getClassTypeDescription() {
    $s = t('a generic vehicle');
    return $s;
  }

  /**
   * Returns class description.
   */
  public function getDescription() {
    $s = t('This is') . ' ';
    $s .= $this->getClassTypeDescription();
    $s .= ' ' . t('of color') . ' ';
    $s .= $this->color;
    $s .= '.';
    return $s;
  }

  /**
   * Implements ColorInterface.
   */
  public function getColor() {
    return $this->color;
  }

}
