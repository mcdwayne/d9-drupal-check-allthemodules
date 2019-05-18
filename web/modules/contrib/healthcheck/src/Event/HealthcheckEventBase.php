<?php

namespace Drupal\healthcheck\Event;

use Drupal\healthcheck\Report\ReportInterface;
use Symfony\Component\EventDispatcher\Event;

class HealthcheckEventBase extends Event implements HealthcheckEventInterface {

  /**
   * The event code.
   *
   * @var string
   *
   * @see \Drupal\healthcheck\Event\HealthcheckEvents
   */
  protected $event;

  /**
   * The report.
   *
   * @var \Drupal\healthcheck\Report\ReportInterface
   */
  protected $report;

  /**
   * HealthcheckEventBase constructor.
   *
   * @param $event
   *   The event code.
   * @param \Drupal\healthcheck\Report\ReportInterface $report
   *   The report.
   */
  public function __construct($event, ReportInterface $report) {
    $this->event = $event;
    $this->report = $report;
  }

  /**
   * {@inheritdoc}
   */
  public function getEvent() {
    return $this->event;
  }

  /**
   * {@inheritdoc}
   */
  public function getReport() {
    return $this->report;
  }
}
