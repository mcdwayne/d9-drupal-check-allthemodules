<?php


namespace Drupal\healthcheck\Event;


use Drupal\healthcheck\Report\ReportInterface;

/**
 * Represents a when Healthcheck runs a new report in the background.
 */
class HealthcheckCronEvent extends HealthcheckEventBase {

  public function __construct(ReportInterface $report) {
    parent::__construct(HealthcheckEvents::CHECK_RUN, $report);
  }
}
