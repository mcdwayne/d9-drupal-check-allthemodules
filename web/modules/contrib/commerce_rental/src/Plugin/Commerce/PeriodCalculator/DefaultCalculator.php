<?php

namespace Drupal\commerce_rental\Plugin\Commerce\PeriodCalculator;

use Drupal\commerce_rental\Entity\RentalPeriod;
use Drupal\commerce_rental\PeriodCalculatorResponse;

/**
 * Provides the Default period calculator.
 *
 * @PeriodCalculator(
 *   id = "default",
 *   name = @Translation("Default"),
 *   traits = { },
 * )
 */
class DefaultCalculator extends PeriodCalculatorPluginBase {


  /**
   * @inheritdoc
   */
  public function calculate($start_date, $end_date, $period) {
    // stores how many times this period should be applied when calculating price.
    $quantity = 0;

    // clone dates so we don't accidentally modify them.
    $start_clone = clone $start_date;
    $end_clone = clone $end_date;

    // Add one day to the end date so that it is included in the calculations
    // TODO: Make it an option to include the end date in the calculation.
    $end_clone->modify('+1 Day');
    $time_units = $period->getTimeUnits();
    switch ($period->getGranularity()) {

      case RentalPeriod::GRANULARITY_DAYS:
        $interval = $end_clone->diff($start_clone);
        $days_left = $days = $interval->days;
        // keep applying the current period until the rental days left is greater than the days required for the period.
        while ($days_left >= $time_units) {
          $days_left -= $time_units;
          $quantity++;
          // This lets the next period know when to begin its calculations.
          $start_clone->modify('+ ' . $time_units . ' days');
        }
        break;

      case RentalPeriod::GRANULARITY_HOURS:

        $interval = $end_clone->diff($start_clone);
        $hours_left = $interval->h;
        // keep applying the current period until the rental hours left is greater than the hours required for the period.
        while ($hours_left >= $time_units) {
          $hours_left -= $time_units;
          $quantity++;
          // This lets the next period know when to begin its calculations.
          $start_clone->modify('+ ' . $time_units . ' hours');
        }
        break;
    }

    return new PeriodCalculatorResponse($quantity, $start_clone);
  }
}