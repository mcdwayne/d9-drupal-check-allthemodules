<?php


namespace Drupal\healthcheck\Event;


use Drupal\healthcheck\Report\ReportInterface;

/**
 * Represents a run of a new report.
 */
class HealthcheckRunEvent extends HealthcheckEventBase {

  public function __construct(ReportInterface $report) {
    parent::__construct(HealthcheckEvents::CHECK_RUN, $report);
  }

}
