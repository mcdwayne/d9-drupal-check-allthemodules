<?php

namespace Drupal\cron_service;

/**
 * Cron service interface with manual control when it should be executed.
 */
interface TimeControllingCronServiceInterface extends CronServiceInterface {

  /**
   * Checks if the service should be executed right now.
   *
   * @return bool
   *   TRUE if service should be executed.
   */
  public function shouldRunNow(): bool;

}
