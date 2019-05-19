<?php

/**
 * @file
 * Contains \Drupal\temporal\TemporalRangedHistoryServiceInterface.
 */

namespace Drupal\temporal;

/**
 * Interface TemporalRangedHistoryServiceInterface.
 *
 * @package Drupal\temporal
 */
interface TemporalRangedHistoryServiceInterface {

  /**
   * Get ranged history for a set of temporal types.
   *
   * @param string|array $temporal_types
   * @param integer $start_date
   * @param integer $end_date
   * @param \DateInterval $interval
   * @param \DateTimeZone|NULL $timezone
   * @return TemporalRangedHistoryInterface
   */
  public function getRangedHistory($temporal_types, $start_date, $end_date, \DateInterval $interval, \DateTimeZone $timezone = NULL);

}
