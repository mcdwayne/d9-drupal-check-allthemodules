<?php

namespace Drupal\commerce_rental\Entity;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Defines the interface for rental variations.
 */
interface RentalPeriodInterface extends ContentEntityInterface {

  /**
   * Gets the rental rate's granularity.
   *
   * @return string
   *   The rental rate granularity.
   */
  public function getGranularity();

  /**
   * Sets the rental rate's granularity.
   *
   * @param string $granularity
   *   The rental rate granularity.
   *
   * @return $this
   */
  public function setGranularity($granularity);

  /**
   * Gets the rental rate time units.
   *
   * @return int
   *   The rental rate time units.
   */
  public function getTimeUnits();

  /**
   * Sets the rental rate time units.
   *
   * @param int $time_units
   *   The rental rate time units.
   *
   * @return $this
   */
  public function setTimeUnits($time_units);

  /**
   * Calculates the interval of time this rate can utilize given a start and end date.
   *
   * @param \DateTime $start_date
   *   The day the rental begins.
   *
   * @param \DateTime $end_date
   *   The day the rental ends.
   *
   * @return array
   *   Array containing the number of times the rate was applied and the new start date.
   *
   */

  public function calculatePeriod($start_date, $end_date);
}
