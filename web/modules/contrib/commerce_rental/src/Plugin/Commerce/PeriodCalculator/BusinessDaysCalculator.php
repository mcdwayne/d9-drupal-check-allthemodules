<?php

namespace Drupal\commerce_rental\Plugin\Commerce\PeriodCalculator;

use Drupal\commerce_rental\Entity\RentalPeriod;
use Drupal\commerce_rental\PeriodCalculatorResponse;

/**
 * Provides the Business Days period calculator.
 *
 * @PeriodCalculator(
 *   id = "business_days",
 *   name = @Translation("Business Days"),
 *   traits = {  },
 * )
 */
class BusinessDaysCalculator extends PeriodCalculatorPluginBase {

  /**
   * {@inheritdoc}
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

        $period = new \DatePeriod($start_clone, new \DateInterval('P1D'), $end_clone);
        $billable_days = 0;
        $weekend_days = 0;

        foreach ($period as $dt) {
          $curr = $dt->format('D');
          // exit the loop if the period days required is greater than the days we have iteperiodd through.
          if ($time_units > $days_left + $billable_days || $quantity == $days) {
            //dpm('Exit(' . $period->label() . '): TimeUnits > DaysLeft + BillableDays');
            break;
          }
          if ($curr == 'Sat' || $curr == 'Sun') {
            $weekend_days++;
          } else {
            $billable_days++;
          }
          // apply the period if the billable days is greater or equal to time units.
          if ($days_left >= 0 && $billable_days >= $time_units) {
            $quantity++;
            // modify the new start date based on how many weekend days were skipped and how many time units this period has.
            $start_clone->modify('+' . ($time_units + $weekend_days). ' days');
            // since we applied a period, set billable days back to zero.
            $billable_days = 0;
            // fixes issue for 1-day periods exiting too early
            if ($time_units == $days_left)
              continue;
          }
          $days_left--;
        }

        break;

      case RentalPeriod::GRANULARITY_HOURS:
        $period = new \DatePeriod($start_clone, \DateInterval::createFromDateString('1 hour'), $end_clone);
        $interval = $end_clone->diff($start_clone);
        $hours_left = $interval->h;

        foreach ($period as $dt) {
          $curr = $dt->format('D');
          if ($curr == 'Sat' || $curr == 'Sun') {
            $start_clone->modify('+1 day');
          } else {
            $hours_left -= $time_units;
            $quantity++;
          }
        }
        break;
    }

    return new PeriodCalculatorResponse($quantity, $start_clone);

  }
}