<?php

namespace Drupal\healthcheck;

/**
 * Interface HealthcheckServiceInterface.
 */
interface HealthcheckServiceInterface {

  /**
   * Gets the timestamp of the last healthcheck run.
   *
   * @return int
   *   A UNIX timestamp of when the last healthcheck was run.
   */
  public function getLastTimestamp();

  /**
   * Set the time of the last healthcheck.
   *
   * @param int $last
   *   The time of the last healthcheck as a UNIX timestamp.
   */
  public function setLastTimestamp($last);

  /**
   * Gets how often to run a healthcheck in seconds.
   *
   * @return int
   *   The number of seconds between each healthcheck.
   */
  public function getInterval();

  /**
   * Run report.
   *
   * @return \Drupal\healthcheck\Report\ReportInterface
   *   The report.
   */
  public function runReport();

  /**
   * Enqueues a healthcheck run in cron if the interval has elapsed.
   */
  public function cron();

}
