<?php

namespace Drupal\commerce_rental\Entity;

use Drupal\commerce\Entity\CommerceBundleEntityInterface;

/**
 * Defines the interface for rental variation types.
 */
interface RentalPeriodTypeInterface extends CommerceBundleEntityInterface {

  /**
   *  Returns an array of all the calculator plugin definitions available.
   *
   * @return array
   *  An array of plugin definitions
   */
  public function getCalculatorTypesList();

  /**
   *  Returns the rental rate calculator id
   *
   * @return string
   *  Returns the active calculator id.
   */
  public function getCalculatorId();

  /**
   *  Returns the rental rate calculator object
   *
   * @return \Drupal\commerce_rental\Plugin\RateCalculator\PeriodCalculatorPluginInterface
   *  Returns the active calculator.
   */
  public function getCalculator();

  /**
   * Sets the rental rate's calculator.
   *
   * @param string $calculator
   *   The rental rate calculator ID.
   *
   * @return $this
   */
  public function setCalculatorId($calculator);

}
