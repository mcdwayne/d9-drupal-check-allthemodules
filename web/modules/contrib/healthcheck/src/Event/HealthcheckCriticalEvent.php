<?php


namespace Drupal\healthcheck\Event;


use Drupal\healthcheck\Report\ReportInterface;

/**
 * Represents critical finding was discovered
 */
class HealthcheckCriticalEvent extends HealthcheckEventBase {

  public function __construct(ReportInterface $report) {
    parent::__construct(HealthcheckEvents::CHECK_CRITICAL, $report);
  }

}