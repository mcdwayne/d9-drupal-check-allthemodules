<?php

namespace Drupal\cron_service;

/**
 * Interface for services should be executed by cron.
 */
interface CronServiceInterface {

  /**
   * This method will be called by CronServiceManager.
   */
  public function execute();

}
