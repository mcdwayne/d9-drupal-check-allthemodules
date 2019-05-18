<?php

namespace Drupal\cron_service;

/**
 * Cron service with scheduled next run.
 */
interface ScheduledCronServiceInterface extends CronServiceInterface {

  /**
   * Returns the next execution time.
   *
   * Returns the timestamp before which the service must not be executed.
   * Because of cron implementation the exact time when the service will be
   * executed can't be evaluated.
   *
   * @return int
   *   Unix Timestamp.
   */
  public function getNextExecutionTime(): int;

}
