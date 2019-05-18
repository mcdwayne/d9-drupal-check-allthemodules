<?php

namespace Drupal\commerce_rental\Plugin\Commerce\PeriodCalculator;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

interface PeriodCalculatorPluginInterface extends PluginInspectionInterface, ContainerFactoryPluginInterface {

  /**
   * Return the name of the reusable form plugin.
   *
   * @return string
   */
  public function getName();

  /**
   * Determines how many times a rental period should be applied.
   *
   * @param $start_date \DateTime
   *  The day the rental starts
   *
   * @param $end_date \DateTime
   *  The day the rental ends
   *
   * @param $period \Drupal\commerce_rental\Entity\RentalPeriod
   *  The rental period object
   *
   * @return \Drupal\commerce_rental\PeriodCalculatorResponse
   */
  public function calculate($start_date, $end_date, $period);



}