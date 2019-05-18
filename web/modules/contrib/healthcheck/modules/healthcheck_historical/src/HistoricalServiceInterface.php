<?php

namespace Drupal\healthcheck_historical;

use Drupal\healthcheck\Report\ReportInterface;

/**
 * Interface HistoricalServiceInterface.
 */
interface HistoricalServiceInterface {

  /**
   * Saves the report to the database.
   *
   * @param \Drupal\healthcheck\Report\ReportInterface $report
   *   The report to save.
   *
   * @return int|null
   *   The report ID.
   */
  public function saveReport(ReportInterface $report);

  /**
   * Run cron tasks.
   */
  public function cron();
}
